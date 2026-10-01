<?php

namespace Tests\Feature;

use App\Mail\LicenseExpiringMail;
use App\Models\License;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LicenseReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_active_expiring_email_contacts_are_reminded_once(): void
    {
        Mail::fake();
        $eligible = License::factory()->create(['contact' => 'client@example.com', 'expires_at' => now()->addDays(5)]);
        License::factory()->create(['contact' => '@telegram_user', 'expires_at' => now()->addDays(5)]);
        License::factory()->create(['contact' => 'later@example.com', 'expires_at' => now()->addDays(20)]);
        License::factory()->revoked()->create(['contact' => 'revoked@example.com', 'expires_at' => now()->addDays(5)]);

        $this->assertSame(0, Artisan::call('licenses:remind-expiring'));
        $this->assertSame(0, Artisan::call('licenses:remind-expiring'));

        Mail::assertSent(LicenseExpiringMail::class, 1);
        Mail::assertSent(LicenseExpiringMail::class, fn (LicenseExpiringMail $mail): bool => $mail->license->id === $eligible->id
            && $mail->hasTo('client@example.com'));
        $this->assertTrue($eligible->fresh()->expiry_reminded_for->equalTo($eligible->expires_at));
    }

    public function test_renewed_license_can_receive_a_new_expiry_reminder(): void
    {
        Mail::fake();
        $license = License::factory()->create(['contact' => 'client@example.com', 'expires_at' => now()->addDays(3)]);

        Artisan::call('licenses:remind-expiring');
        $license->update(['expires_at' => now()->addDays(6)]);
        Artisan::call('licenses:remind-expiring');

        Mail::assertSent(LicenseExpiringMail::class, 2);
        $this->assertTrue($license->fresh()->expiry_reminded_for->equalTo($license->expires_at));
    }
}
