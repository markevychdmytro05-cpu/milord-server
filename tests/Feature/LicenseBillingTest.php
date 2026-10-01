<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\LicenseAudit;
use App\Models\LicensePayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class LicenseBillingTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function paymentData(string $operation = 'payment'): array
    {
        return [
            'operation' => $operation,
            'amount' => '125.45',
            'paid_at' => now()->format('Y-m-d H:i:s'),
            'duration_days' => 30,
            'reference' => 'BANK-123',
            'idempotency_key' => (string) Str::uuid(),
        ];
    }

    public function test_manager_records_payment_without_changing_license_terms(): void
    {
        $manager = User::factory()->create();
        $license = License::factory()->create(['price' => 200]);
        $expiresAt = $license->expires_at->toDateTimeString();

        $this->actingAs($manager, 'backpack')->post("/admin/license/{$license->id}/payments", $this->paymentData() + [
            'currency' => 'EUR', 'recorded_by' => 999, 'amount_cents' => 1, 'voided_at' => now(),
        ])->assertRedirect(route('license.show', $license->id))->assertSessionHasNoErrors();

        $payment = LicensePayment::sole();
        $this->assertSame(12545, $payment->amount_cents);
        $this->assertSame('USD', $payment->currency);
        $this->assertSame($manager->id, $payment->recorded_by);
        $this->assertNull($payment->duration_days);
        $this->assertNull($payment->voided_at);
        $this->assertSame($expiresAt, $license->fresh()->expires_at->toDateTimeString());
        $this->assertSame(200, $license->fresh()->price);
        $this->assertDatabaseHas('license_audits', ['license_id' => $license->id, 'event' => 'payment_recorded', 'actor_id' => $manager->id]);
    }

    /** @return array<string, array{string, string, string}> */
    public static function renewalDates(): array
    {
        return [
            'active' => ['2026-10-10 12:00:00', '2026-10-10 12:00:00', '2026-11-09 12:00:00'],
            'expired' => ['2026-09-01 12:00:00', '2026-09-27 12:00:00', '2026-10-27 12:00:00'],
            'expires now' => ['2026-09-27 12:00:00', '2026-09-27 12:00:00', '2026-10-27 12:00:00'],
        ];
    }

    #[DataProvider('renewalDates')]
    public function test_renewal_records_the_correct_paid_period(string $expiry, string $start, string $end): void
    {
        $this->travelTo(Carbon::parse('2026-09-27 12:00:00'));
        $license = License::factory()->create(['expires_at' => $expiry]);

        $this->actingAs(User::factory()->create(), 'backpack')
            ->post("/admin/license/{$license->id}/payments", $this->paymentData('renewal'))
            ->assertSessionHasNoErrors();

        $this->assertSame($end, $license->fresh()->expires_at->toDateTimeString());
        $payment = LicensePayment::sole();
        $this->assertSame($start, $payment->period_starts_at->toDateTimeString());
        $this->assertSame($end, $payment->period_ends_at->toDateTimeString());
        $this->assertSame($expiry, $payment->expires_at_before->toDateTimeString());
        $this->assertDatabaseHas('license_audits', ['license_id' => $license->id, 'event' => 'renewed']);
    }

    public function test_repeated_submission_does_not_double_charge_or_extend(): void
    {
        $this->travelTo(Carbon::parse('2026-09-27 12:00:00'));
        $license = License::factory()->create(['expires_at' => '2026-10-10 12:00:00']);
        $data = $this->paymentData('renewal');
        $this->actingAs(User::factory()->create(), 'backpack');

        $this->post("/admin/license/{$license->id}/payments", $data)->assertSessionHasNoErrors();
        $this->post("/admin/license/{$license->id}/payments", $data)->assertSessionHasNoErrors();

        $this->assertDatabaseCount('license_payments', 1);
        $this->assertSame('2026-11-09 12:00:00', $license->fresh()->expires_at->toDateTimeString());
        $this->assertSame(1, LicenseAudit::where('event', 'renewed')->count());
    }

    public function test_reusing_submission_token_with_different_amount_is_rejected(): void
    {
        $license = License::factory()->create();
        $data = $this->paymentData();
        $this->actingAs(User::factory()->create(), 'backpack');
        $this->post("/admin/license/{$license->id}/payments", $data)->assertSessionHasNoErrors();

        $this->post("/admin/license/{$license->id}/payments", array_replace($data, ['amount' => '999']))
            ->assertSessionHasErrors('idempotency_key');

        $this->assertDatabaseCount('license_payments', 1);
        $this->assertSame(12545, LicensePayment::sole()->amount_cents);
    }

    /** @return array<string, array{string}> */
    public static function invalidAmounts(): array
    {
        return [
            'negative' => ['-1'], 'zero' => ['0.00'], 'too precise' => ['1.001'],
            'exponent' => ['1e3'], 'too large' => ['100000000'], 'text' => ['paid'],
        ];
    }

    #[DataProvider('invalidAmounts')]
    public function test_invalid_amount_does_not_create_payment(string $amount): void
    {
        $license = License::factory()->create();

        $this->actingAs(User::factory()->create(), 'backpack')
            ->post("/admin/license/{$license->id}/payments", array_replace($this->paymentData(), ['amount' => $amount]))
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('license_payments', 0);
    }

    public function test_comma_decimal_amount_is_stored_without_rounding(): void
    {
        $license = License::factory()->create();

        $this->actingAs(User::factory()->create(), 'backpack')
            ->post("/admin/license/{$license->id}/payments", array_replace($this->paymentData(), ['amount' => '10,01']))
            ->assertSessionHasNoErrors();

        $this->assertSame(1001, LicensePayment::sole()->amount_cents);
    }

    public function test_renewal_rejects_missing_duration_and_future_payment_date(): void
    {
        $license = License::factory()->create();

        $this->actingAs(User::factory()->create(), 'backpack')
            ->post("/admin/license/{$license->id}/payments", array_replace($this->paymentData('renewal'), [
                'duration_days' => null, 'paid_at' => now()->addDay()->toDateTimeString(),
            ]))->assertSessionHasErrors([
                'duration_days' => 'Вкажіть кількість днів продовження.',
                'paid_at' => 'Дата оплати не може бути в майбутньому.',
            ]);

        $this->assertDatabaseCount('license_payments', 0);
    }

    public function test_revoked_and_lifetime_licenses_cannot_be_renewed(): void
    {
        $revoked = License::factory()->revoked()->create();
        $lifetime = License::factory()->lifetime()->create();
        $this->actingAs(User::factory()->create(), 'backpack');

        foreach ([$revoked, $lifetime] as $license) {
            $this->post("/admin/license/{$license->id}/payments", $this->paymentData('renewal'))
                ->assertSessionHasErrors('operation');
        }

        $this->assertDatabaseCount('license_payments', 0);
        $this->assertSame(License::STATUS_REVOKED, $revoked->fresh()->status);
        $this->assertNull($lifetime->fresh()->expires_at);
    }

    public function test_guests_and_non_staff_cannot_record_payments(): void
    {
        $license = License::factory()->create();
        $url = "/admin/license/{$license->id}/payments";
        $this->post($url, $this->paymentData())->assertRedirect('/admin/login');

        $this->actingAs(User::factory()->withoutRole()->create(), 'backpack')
            ->post($url, $this->paymentData())->assertRedirect('/admin/login');

        $this->assertDatabaseCount('license_payments', 0);
    }

    public function test_paid_license_cannot_be_deleted(): void
    {
        $payment = LicensePayment::factory()->create();

        $this->actingAs(User::factory()->admin()->create(), 'backpack')
            ->delete("/admin/license/{$payment->license_id}")->assertStatus(409);

        $this->assertModelExists($payment->license);
        $this->assertModelExists($payment);
        $this->assertSame(0, LicenseAudit::where('event', 'deleted')->count());
    }

    public function test_only_admin_can_void_payment_and_it_keeps_license_expiry(): void
    {
        $payment = LicensePayment::factory()->create();
        $expiresAt = $payment->license->expires_at->toDateTimeString();
        $url = "/admin/license/{$payment->license_id}/payments/{$payment->id}/void";
        $this->actingAs(User::factory()->create(), 'backpack')->post($url, ['reason' => 'Duplicate'])->assertForbidden();
        $this->assertNull($payment->fresh()->voided_at);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin, 'backpack')->post($url, ['reason' => 'Duplicate'])->assertSessionHasNoErrors();
        $this->post($url, ['reason' => 'Duplicate'])->assertSessionHasNoErrors();

        $this->assertNotNull($payment->fresh()->voided_at);
        $this->assertSame($admin->id, $payment->fresh()->voided_by);
        $this->assertSame('Duplicate', $payment->fresh()->void_reason);
        $this->assertSame($expiresAt, $payment->license->fresh()->expires_at->toDateTimeString());
        $this->assertSame(1, LicenseAudit::where('event', 'payment_voided')->count());
    }

    public function test_voiding_latest_renewal_restores_previous_expiry(): void
    {
        $this->travelTo(Carbon::parse('2026-09-27 12:00:00'));
        $license = License::factory()->create(['expires_at' => '2026-10-10 12:00:00']);
        $this->actingAs(User::factory()->admin()->create(), 'backpack');
        $this->post("/admin/license/{$license->id}/payments", $this->paymentData('renewal'))->assertSessionHasNoErrors();
        $payment = LicensePayment::sole();

        $this->post("/admin/license/{$license->id}/payments/{$payment->id}/void", ['reason' => 'Duplicate'])
            ->assertSessionHasNoErrors();

        $this->assertSame('2026-10-10 12:00:00', $license->fresh()->expires_at->toDateTimeString());
        $this->assertNotNull($payment->fresh()->voided_at);
        $this->assertTrue(LicenseAudit::where('event', 'payment_voided')->sole()->metadata['expiry_restored']);
    }

    public function test_earlier_renewal_cannot_be_voided_after_later_renewal(): void
    {
        $this->travelTo(Carbon::parse('2026-09-27 12:00:00'));
        $license = License::factory()->create(['expires_at' => '2026-10-10 12:00:00']);
        $this->actingAs(User::factory()->admin()->create(), 'backpack');
        $this->post("/admin/license/{$license->id}/payments", $this->paymentData('renewal'))->assertSessionHasNoErrors();
        $first = LicensePayment::sole();
        $this->post("/admin/license/{$license->id}/payments", $this->paymentData('renewal'))->assertSessionHasNoErrors();
        $expiresAt = $license->fresh()->expires_at->toDateTimeString();

        $this->post("/admin/license/{$license->id}/payments/{$first->id}/void", ['reason' => 'Duplicate'])
            ->assertSessionHasErrors('reason');

        $this->assertNull($first->fresh()->voided_at);
        $this->assertSame($expiresAt, $license->fresh()->expires_at->toDateTimeString());
    }

    public function test_legacy_renewal_without_previous_expiry_requires_manual_correction(): void
    {
        $payment = LicensePayment::factory()->create([
            'operation' => LicensePayment::OPERATION_RENEWAL,
            'period_starts_at' => now(),
            'period_ends_at' => now()->addDays(30),
        ]);
        $expiresAt = $payment->license->expires_at->toDateTimeString();
        $this->actingAs(User::factory()->admin()->create(), 'backpack');

        $this->post("/admin/license/{$payment->license_id}/payments/{$payment->id}/void", ['reason' => 'Duplicate'])
            ->assertSessionHasErrors('reason');

        $this->assertNull($payment->fresh()->voided_at);
        $this->assertSame($expiresAt, $payment->license->fresh()->expires_at->toDateTimeString());
    }

    public function test_void_requires_reason_and_payment_must_belong_to_license(): void
    {
        $payment = LicensePayment::factory()->create();
        $other = License::factory()->create();
        $this->actingAs(User::factory()->admin()->create(), 'backpack');

        $this->post("/admin/license/{$payment->license_id}/payments/{$payment->id}/void", [])->assertSessionHasErrors('reason');
        $this->post("/admin/license/{$other->id}/payments/{$payment->id}/void", ['reason' => 'Wrong record'])->assertNotFound();

        $this->assertNull($payment->fresh()->voided_at);
    }

    public function test_dashboard_counts_received_payments_by_payment_date_and_excludes_voids(): void
    {
        $this->travelTo(Carbon::parse('2026-09-27 12:00:00'));
        $license = License::factory()->create(['price' => 9000, 'created_at' => '2026-08-01']);
        LicensePayment::factory()->for($license)->create(['amount_cents' => 12545]);
        LicensePayment::factory()->for($license)->create(['amount_cents' => 1000, 'paid_at' => '2026-08-31 23:59:59']);
        LicensePayment::factory()->for($license)->create(['amount_cents' => 2000, 'voided_at' => now()]);
        License::factory()->create(['price' => 7000]);

        $this->actingAs(User::factory()->create(), 'backpack')->get('/admin/dashboard')
            ->assertOk()->assertSee('$125.45')->assertSee('Отримано оплат цього місяця');
    }

    public function test_payment_notes_and_reference_are_escaped_on_license_page(): void
    {
        $payment = LicensePayment::factory()->create(['note' => '<script>alert(1)</script>', 'reference' => '<b>bank</b>']);

        $this->actingAs(User::factory()->create(), 'backpack')->get("/admin/license/{$payment->license_id}/show")
            ->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;b&gt;bank&lt;/b&gt;', false);
    }

    public function test_audit_failure_rolls_back_payment_and_renewal_together(): void
    {
        $license = License::factory()->create();
        $expiresAt = $license->expires_at->toDateTimeString();
        $auditCount = LicenseAudit::count();
        $eventName = 'eloquent.creating: '.LicenseAudit::class;
        Exceptions::fake();
        Event::listen($eventName, function (LicenseAudit $audit): void {
            if ($audit->event === 'renewed') {
                throw new RuntimeException('Audit storage unavailable');
            }
        });

        try {
            $this->actingAs(User::factory()->create(), 'backpack')
                ->post("/admin/license/{$license->id}/payments", $this->paymentData('renewal'))
                ->assertInternalServerError();
        } finally {
            Event::forget($eventName);
        }

        $this->assertDatabaseCount('license_payments', 0);
        $this->assertSame($expiresAt, $license->fresh()->expires_at->toDateTimeString());
        $this->assertSame($auditCount, LicenseAudit::count());
        Exceptions::assertReported(RuntimeException::class);
    }
}
