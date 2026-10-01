<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LicensePaymentRequest;
use App\Models\License;
use App\Models\LicenseAudit;
use App\Models\LicensePayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Prologue\Alerts\Facades\Alert;

class LicensePaymentController extends Controller
{
    public function store(LicensePaymentRequest $request, int $id): RedirectResponse
    {
        $data = $request->validated();
        $attributes = [
            'operation' => $data['operation'],
            'amount_cents' => $request->amountInCents(),
            'currency' => 'USD',
            'paid_at' => Carbon::parse($data['paid_at'])->setTimezone(config('app.timezone'))->toDateTimeString(),
            'duration_days' => isset($data['duration_days']) ? (int) $data['duration_days'] : null,
            'reference' => $data['reference'] ?? null,
            'note' => $data['note'] ?? null,
        ];
        $requestHash = hash('sha256', json_encode($attributes, JSON_THROW_ON_ERROR));

        DB::transaction(function () use ($id, $data, $attributes, $requestHash): void {
            $license = License::whereKey($id)->lockForUpdate()->firstOrFail();
            $existing = $license->payments()->where('idempotency_key', $data['idempotency_key'])->first();

            if ($existing) {
                if (! hash_equals($existing->request_hash, $requestHash)) {
                    throw ValidationException::withMessages(['idempotency_key' => 'Цю форму вже оброблено з іншими даними. Оновіть сторінку.']);
                }

                return;
            }

            if ($attributes['operation'] === LicensePayment::OPERATION_RENEWAL) {
                if ($license->status !== License::STATUS_ACTIVE || $license->expires_at === null) {
                    throw ValidationException::withMessages(['operation' => 'Продовжити можна лише невідкликану ліцензію з обмеженим строком.']);
                }

                $attributes['expires_at_before'] = $license->expires_at->copy();
                $startsAt = $license->expires_at->isFuture() ? $license->expires_at->copy() : now();
                $endsAt = $startsAt->copy()->addDays($attributes['duration_days']);
                $attributes['period_starts_at'] = $startsAt;
                $attributes['period_ends_at'] = $endsAt;
                $license->update(['expires_at' => $endsAt]);
            }

            $payment = $license->payments()->create($attributes + [
                'idempotency_key' => $data['idempotency_key'],
                'request_hash' => $requestHash,
                'recorded_by' => backpack_user()->id,
            ]);

            LicenseAudit::record($license, $payment->operation === LicensePayment::OPERATION_RENEWAL ? 'renewed' : 'payment_recorded', metadata: [
                'payment_id' => $payment->id,
                'amount_cents' => $payment->amount_cents,
                'currency' => $payment->currency,
                'duration_days' => $payment->duration_days,
                'reference' => $payment->reference,
            ]);
        }, attempts: 3);

        Alert::success('Оплату записано. Повторне надсилання цієї форми не створює нової оплати.')->flash();

        return redirect()->route('license.show', ['id' => $id]);
    }

    public function void(Request $request, int $id, int $paymentId): RedirectResponse
    {
        abort_unless(backpack_user()?->can('payments_void'), 403);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        $expiryRestored = DB::transaction(function () use ($id, $paymentId, $data): bool {
            $license = License::whereKey($id)->lockForUpdate()->firstOrFail();
            $payment = $license->payments()->findOrFail($paymentId);

            if ($payment->voided_at !== null) {
                return false;
            }

            $expiryRestored = $payment->operation === LicensePayment::OPERATION_RENEWAL;

            if ($expiryRestored) {
                if ($payment->expires_at_before === null || $payment->period_ends_at === null) {
                    throw ValidationException::withMessages(['reason' => 'Для цього старого продовження немає даних для автоматичного відновлення строку. Зверніться до адміністратора.']);
                }

                if ($license->payments()->where('id', '>', $payment->id)
                    ->where('operation', LicensePayment::OPERATION_RENEWAL)->whereNull('voided_at')->exists()
                    || $license->expires_at === null
                    || ! $license->expires_at->equalTo($payment->period_ends_at)) {
                    throw ValidationException::withMessages(['reason' => 'Строк ліцензії вже змінено після цієї оплати. Спершу перевірте пізніші операції.']);
                }

                $license->update(['expires_at' => $payment->expires_at_before]);
            }

            $payment->voided_at = now();
            $payment->voided_by = backpack_user()->id;
            $payment->void_reason = $data['reason'];
            $payment->save();

            LicenseAudit::record($license, 'payment_voided', metadata: [
                'payment_id' => $payment->id,
                'reason' => $data['reason'],
                'expiry_restored' => $expiryRestored,
            ]);

            return $expiryRestored;
        }, attempts: 3);

        Alert::success($expiryRestored
            ? 'Помилкове продовження скасовано. Попередній строк ліцензії відновлено.'
            : 'Помилковий запис оплати скасовано. Строк ліцензії не змінено.')->flash();

        return redirect()->route('license.show', ['id' => $id]);
    }
}
