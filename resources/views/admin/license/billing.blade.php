<div class="card mt-3">
    <div class="card-header"><strong>Оплати та продовження</strong></div>
    @if (backpack_user()->can('payments_create'))
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="POST" action="{{ route('license.payments.store', ['id' => $entry->id]) }}" class="d-print-none">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">
            <div class="row">
                <div class="form-group col-md-4">
                    <label for="payment-operation">Дія</label>
                    <select id="payment-operation" name="operation" class="form-control" required>
                        <option value="payment" @selected(old('operation') === 'payment')>Записати отриману оплату</option>
                        @if ($entry->status === \App\Models\License::STATUS_ACTIVE && $entry->expires_at)
                            <option value="renewal" @selected(old('operation') === 'renewal')>Записати оплату та продовжити</option>
                        @endif
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label for="payment-amount">Сума, USD</label>
                    <input id="payment-amount" name="amount" class="form-control" type="text" inputmode="decimal" value="{{ old('amount', $entry->price) }}" required>
                </div>
                <div class="form-group col-md-3">
                    <label for="payment-date">Оплачено ({{ config('app.timezone') }})</label>
                    <input id="payment-date" name="paid_at" class="form-control" type="datetime-local" value="{{ old('paid_at', now()->format('Y-m-d\TH:i')) }}" required>
                </div>
                <div class="form-group col-md-3">
                    <label for="payment-duration">Днів продовження</label>
                    <input id="payment-duration" name="duration_days" class="form-control" type="number" min="1" max="3650" value="{{ old('duration_days', $entry->package?->duration_days ?? 30) }}">
                    <small class="text-muted">Враховується лише для продовження.</small>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-md-4">
                    <label for="payment-reference">Номер переказу / квитанції</label>
                    <input id="payment-reference" name="reference" class="form-control" maxlength="255" value="{{ old('reference') }}">
                </div>
                <div class="form-group col-md-8">
                    <label for="payment-note">Примітка до оплати</label>
                    <input id="payment-note" name="note" class="form-control" maxlength="2000" value="{{ old('note') }}">
                </div>
            </div>
            <p class="text-muted small">Записуйте лише вже отримані кошти. Продовження додається до чинного строку; для простроченої ліцензії – від поточного моменту. Звичайний запис оплати не змінює строк.</p>
            <button class="btn btn-primary" type="submit">Зберегти оплату</button>
        </form>
    </div>
    @endif
    @if (backpack_user()->can('payments_view'))
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Оплачено</th><th>Сума</th><th>Період / призначення</th><th>Записав</th><th>Деталі</th></tr></thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr>
                        <td>{{ $payment->paid_at->format('d.m.Y H:i') }}</td>
                        <td class="text-nowrap">
                            {{ $payment->amountLabel() }}
                            @if ($payment->voided_at)
                                <div class="text-danger small">Запис скасовано</div>
                            @endif
                        </td>
                        <td>
                            @if ($payment->period_starts_at)
                                {{ $payment->period_starts_at->format('d.m.Y H:i') }} – {{ $payment->period_ends_at->format('d.m.Y H:i') }}
                            @else
                                Оплата без зміни строку
                            @endif
                        </td>
                        <td>{{ $payment->recorder?->name ?? '–' }}</td>
                        <td>
                            <div>{{ $payment->reference }}</div>
                            <div>{{ $payment->note }}</div>
                            @if ($payment->voided_at)
                                <div>{{ $payment->void_reason }}</div>
                            @elseif (backpack_user()->can('payments_void'))
                                <details class="mt-2 d-print-none">
                                    <summary>Скасувати помилковий запис</summary>
                                    <form method="POST" action="{{ route('license.payments.void', ['id' => $entry->id, 'paymentId' => $payment->id]) }}">
                                        @csrf
                                        <label for="void-reason-{{ $payment->id }}" class="small">Причина скасування</label>
                                        <input id="void-reason-{{ $payment->id }}" name="reason" class="form-control form-control-sm" maxlength="255" required>
                                        <p class="small text-muted my-2">Гроші не повертаються. Якщо це останнє продовження і строк згодом не змінювали, попередній строк буде відновлено.</p>
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Скасувати запис</button>
                                    </form>
                                </details>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">Оплат ще не записано. Ціна ліцензії сама по собі не підтверджує оплату.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $payments->withQueryString()->links() }}</div>
    @endif
</div>
