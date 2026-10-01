<?php

namespace App\Console\Commands;

use App\Mail\LicenseExpiringMail;
use App\Models\License;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

#[Signature('licenses:remind-expiring')]
#[Description('Надіслати нагадування про ліцензії, строк яких завершується протягом семи днів')]
class SendLicenseExpiryReminders extends Command
{
    public function handle(): int
    {
        $sent = 0;
        $failed = 0;

        License::query()
            ->where('status', License::STATUS_ACTIVE)
            ->whereNotNull('contact')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays(7))
            ->where(function ($query): void {
                $query->whereNull('expiry_reminded_for')
                    ->orWhereColumn('expiry_reminded_for', '!=', 'expires_at');
            })
            ->chunkById(100, function ($licenses) use (&$sent, &$failed): void {
                foreach ($licenses as $license) {
                    if (filter_var($license->contact, FILTER_VALIDATE_EMAIL) === false) {
                        continue;
                    }

                    try {
                        Mail::to($license->contact)->send(new LicenseExpiringMail($license));
                        DB::table('licenses')->where('id', $license->id)
                            ->where('expires_at', $license->expires_at)
                            ->update(['expiry_reminded_for' => $license->expires_at]);
                        $sent++;
                    } catch (Throwable $exception) {
                        report($exception);
                        $this->error("Не вдалося надіслати нагадування для ліцензії #{$license->id}.");
                        $failed++;
                    }
                }
            });

        $this->info("Нагадувань надіслано: {$sent}. Помилок: {$failed}.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
