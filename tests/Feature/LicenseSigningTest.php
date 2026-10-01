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

    private const DEVICE = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    /** @return array<string, string> */
    private function signedRequest(string $action, string $key): array
    {
        $pair = sodium_crypto_sign_keypair();
        $publicKey = base64_encode(sodium_crypto_sign_publickey($pair));
        $nonce = bin2hex(random_bytes(24));
        $requestTime = now()->timestamp;
        $message = implode("\n", ['license-v2', $action, License::normalizeKey($key), self::DEVICE, $nonce, $requestTime, $publicKey, '', '']);

        return [
            'key' => $key,
            'device_id' => self::DEVICE,
            'nonce' => $nonce,
            'request_time' => $requestTime,
            'device_public_key' => $publicKey,
            'device_signature' => base64_encode(sodium_crypto_sign_detached($message, sodium_crypto_sign_secretkey($pair))),
        ];
    }

    public function test_production_rejects_unsigned_activation_and_rolls_back_device_binding(): void
    {
        $license = License::factory()->create();
        config(['license.signing_secret_key' => null, 'license.require_signature' => false]);
        $this->app['env'] = 'production';
        Exceptions::fake();

        $this->postJson('/api/v1/license/activate', $this->signedRequest('activate', $license->key))
            ->assertInternalServerError();

        $this->assertDatabaseCount('license_activations', 0);
        $this->assertSame(0, $license->audits()->where('event', 'device_activated')->count());
        Exceptions::assertReported(RuntimeException::class);
    }

    public function test_invalid_signing_key_rolls_back_heartbeat(): void
    {
        $license = License::factory()->create();
        $activation = $license->activations()->create(['device_id' => self::DEVICE, 'last_seen_at' => '2026-01-01 00:00:00']);
        config(['license.signing_secret_key' => 'invalid']);
        Exceptions::fake();

        $this->postJson('/api/v1/license/check', $this->signedRequest('check', $license->key))
            ->assertInternalServerError();

        $this->assertSame('2026-01-01 00:00:00', $activation->fresh()->last_seen_at->toDateTimeString());
        Exceptions::assertReported(RuntimeException::class);
    }

    public function test_staging_can_require_signed_responses(): void
    {
        $license = License::factory()->create();
        config(['license.signing_secret_key' => null, 'license.require_signature' => true]);
        Exceptions::fake();

        $this->postJson('/api/v1/license/activate', $this->signedRequest('activate', $license->key))
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
