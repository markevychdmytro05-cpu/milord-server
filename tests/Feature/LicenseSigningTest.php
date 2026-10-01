<?php

namespace Tests\Feature;

use App\Models\License;
use App\Support\ResponseSigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use RuntimeException;
use Tests\TestCase;

class LicenseSigningTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_rejects_unsigned_activation_and_rolls_back_device_binding(): void
    {
        $license = License::factory()->create();
        config(['license.signing_secret_key' => null, 'license.require_signature' => false]);
        $this->app['env'] = 'production';
        Exceptions::fake();

        $this->postJson('/api/v1/license/activate', ['key' => $license->key, 'device_id' => 'device-first'])
            ->assertInternalServerError();

        $this->assertDatabaseCount('license_activations', 0);
        $this->assertSame(0, $license->audits()->where('event', 'device_activated')->count());
        Exceptions::assertReported(RuntimeException::class);
    }

    public function test_invalid_signing_key_rolls_back_heartbeat(): void
    {
        $license = License::factory()->create();
        $activation = $license->activations()->create(['device_id' => 'device-first', 'last_seen_at' => '2026-01-01 00:00:00']);
        config(['license.signing_secret_key' => 'invalid']);
        Exceptions::fake();

        $this->postJson('/api/v1/license/check', ['key' => $license->key, 'device_id' => 'device-first'])
            ->assertInternalServerError();

        $this->assertSame('2026-01-01 00:00:00', $activation->fresh()->last_seen_at->toDateTimeString());
        Exceptions::assertReported(RuntimeException::class);
    }

    public function test_staging_can_require_signed_responses(): void
    {
        $license = License::factory()->create();
        config(['license.signing_secret_key' => null, 'license.require_signature' => true]);
        Exceptions::fake();

        $this->postJson('/api/v1/license/activate', ['key' => $license->key, 'device_id' => 'device-first'])
            ->assertInternalServerError();

        $this->assertDatabaseCount('license_activations', 0);
        Exceptions::assertReported(RuntimeException::class);
    }

    public function test_doctor_fails_without_key_and_succeeds_with_valid_configuration(): void
    {
        config(['license.signing_secret_key' => null, 'license.require_signature' => false]);
        $this->artisan('license:doctor')->assertExitCode(1);

        $pair = ResponseSigner::generateKeyPair();
        config(['license.signing_secret_key' => $pair['secret'], 'license.offline_grace_hours' => 72, 'license.rate_limit' => 30]);

        $this->artisan('license:doctor')->expectsOutput('Підпис Ed25519 працює.')->assertExitCode(0);
    }

    public function test_doctor_rejects_invalid_limits(): void
    {
        config([
            'license.signing_secret_key' => ResponseSigner::generateKeyPair()['secret'],
            'license.offline_grace_hours' => 0, 'license.rate_limit' => -1,
        ]);

        $this->artisan('license:doctor')
            ->expectsOutput('license.offline_grace_hours має бути більшим за нуль.')
            ->expectsOutput('license.rate_limit має бути більшим за нуль.')
            ->assertExitCode(1);
    }
}
