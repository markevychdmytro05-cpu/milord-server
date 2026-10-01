<?php

namespace Tests\Feature;

use App\Models\License;
use App\Support\ResponseSigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class LicenseApiTest extends TestCase
{
    use RefreshDatabase;

    private const DEVICE = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private const DEVICE_B = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

    private const DEVICE_C = 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc';

    /** @var array<string, string> */
    private array $deviceKeys = [];

    private int $requestCounter = 0;

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function signedRequest(string $action, string $key, string $device, array $extra = []): array
    {
        $pair = $this->deviceKeys[$device] ??= sodium_crypto_sign_keypair();
        $data = [
            'key' => $key,
            'device_id' => $device,
            'device_name' => null,
            'app_version' => null,
            'nonce' => str_pad(dechex(++$this->requestCounter), 48, '0', STR_PAD_LEFT),
            'request_time' => now()->timestamp,
            'device_public_key' => base64_encode(sodium_crypto_sign_publickey($pair)),
        ];
        $data = array_replace($data, $extra);
        $message = implode("\n", [
            'license-v2', $action, License::normalizeKey($data['key']), $data['device_id'],
            $data['nonce'], (string) $data['request_time'], $data['device_public_key'],
            $data['device_name'] ?? '', $data['app_version'] ?? '',
        ]);
        $data['device_signature'] = base64_encode(sodium_crypto_sign_detached($message, sodium_crypto_sign_secretkey($pair)));

        return $data;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function activate(License $license, string $device = self::DEVICE, array $extra = []): TestResponse
    {
        return $this->postJson('/api/v1/license/activate', $this->signedRequest('activate', $license->key, $device, $extra));
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function check(License $license, string $device = self::DEVICE, array $extra = []): TestResponse
    {
        return $this->postJson('/api/v1/license/check', $this->signedRequest('check', $license->key, $device, $extra));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(TestResponse $response): array
    {
        return json_decode($response->json('data'), true);
    }

    public function test_generated_key_has_expected_format(): void
    {
        $license = License::factory()->create();

        $this->assertMatchesRegularExpression('/^[2-9A-HJ-NP-Z]{4}(-[2-9A-HJ-NP-Z]{4}){3}$/', $license->key);
    }

    public function test_activation_binds_device_and_returns_limits(): void
    {
        $license = License::factory()->create(['max_accounts' => 10]);

        $response = $this->activate($license, extra: ['device_name' => 'Office PC']);

        $response->assertOk();
        $data = $this->payload($response);
        $this->assertTrue($data['success']);
        $this->assertSame(10, $data['license']['max_accounts']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{48}$/', $data['nonce']);
        $this->assertSame(self::DEVICE, $data['device_id']);
        $this->assertSame(base64_encode(sodium_crypto_sign_publickey($this->deviceKeys[self::DEVICE])), $data['device_public_key']);
        $this->assertDatabaseHas('license_activations', ['license_id' => $license->id, 'device_id' => self::DEVICE, 'device_name' => 'Office PC']);
    }

    public function test_key_is_accepted_in_any_case_and_without_dashes(): void
    {
        $license = License::factory()->create();

        $this->postJson('/api/v1/license/activate', $this->signedRequest(
            'activate', strtolower(str_replace('-', '', $license->key)), self::DEVICE,
        ))->assertOk();
    }

    public function test_reactivating_same_device_does_not_take_another_slot(): void
    {
        $license = License::factory()->create(['max_devices' => 1]);

        $this->activate($license)->assertOk();
        $this->activate($license)->assertOk();

        $this->assertSame(1, $license->activations()->count());
    }

    public function test_device_limit_is_enforced(): void
    {
        $license = License::factory()->create(['max_devices' => 1]);
        $this->activate($license)->assertOk();

        $response = $this->activate($license, self::DEVICE_B);

        $response->assertStatus(409);
        $this->assertSame('device_limit_reached', $this->payload($response)['error']);
    }

    public function test_unknown_key_is_rejected(): void
    {
        $response = $this->postJson('/api/v1/license/check', $this->signedRequest('check', 'AAAA-BBBB-CCCC-DDDD', self::DEVICE));

        $response->assertNotFound();
        $this->assertSame('license_not_found', $this->payload($response)['error']);
    }

    public function test_revoked_license_is_rejected_even_for_bound_device(): void
    {
        $license = License::factory()->create();
        $this->activate($license)->assertOk();

        $license->update(['status' => License::STATUS_REVOKED]);

        $response = $this->check($license);
        $response->assertForbidden();
        $this->assertSame('license_revoked', $this->payload($response)['error']);
    }

    public function test_expired_license_is_rejected(): void
    {
        $license = License::factory()->expired()->create();

        $response = $this->activate($license);

        $response->assertForbidden();
        $this->assertSame('license_expired', $this->payload($response)['error']);
    }

    public function test_check_requires_prior_activation(): void
    {
        $license = License::factory()->create();

        $response = $this->check($license);

        $response->assertForbidden();
        $this->assertSame('device_not_activated', $this->payload($response)['error']);
    }

    public function test_client_reported_account_count_is_rejected(): void
    {
        $license = License::factory()->create(['max_accounts' => 5]);
        $this->activate($license)->assertOk();

        $this->check($license, extra: ['accounts_used' => 6])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['accounts_used']);

        $this->assertDatabaseHas('license_activations', ['device_id' => self::DEVICE, 'accounts_used' => null]);
    }

    public function test_valid_until_never_exceeds_license_expiry(): void
    {
        config(['license.offline_grace_hours' => 72]);
        $license = License::factory()->create(['expires_at' => now()->addHour()]);

        $data = $this->payload($this->activate($license));

        $this->assertSame($license->expires_at->toIso8601String(), $data['valid_until']);
    }

    public function test_lifetime_license_gets_offline_grace_window(): void
    {
        config(['license.offline_grace_hours' => 72]);
        $this->freezeTime();
        $license = License::factory()->lifetime()->create();

        $data = $this->payload($this->activate($license));

        $this->assertNull($data['license']['expires_at']);
        $this->assertSame(now()->addHours(72)->toIso8601String(), $data['valid_until']);
    }

    public function test_response_is_signed_when_signing_key_is_configured(): void
    {
        $pair = ResponseSigner::generateKeyPair();
        config(['license.signing_secret_key' => $pair['secret']]);
        $license = License::factory()->create();

        $response = $this->activate($license);

        $this->assertTrue(sodium_crypto_sign_verify_detached(
            base64_decode($response->json('signature')),
            $response->json('data'),
            base64_decode($pair['public']),
        ));
    }

    public function test_response_is_unsigned_without_signing_key(): void
    {
        config(['license.signing_secret_key' => null]);
        $license = License::factory()->create();

        $this->assertNull($this->activate($license)->json('signature'));
    }

    public function test_request_validation(): void
    {
        $this->postJson('/api/v1/license/activate', ['key' => 'X'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['device_id']);
    }

    public function test_legacy_request_without_device_proof_is_rejected(): void
    {
        $license = License::factory()->create();

        $this->postJson('/api/v1/license/activate', ['key' => $license->key, 'device_id' => self::DEVICE])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nonce', 'request_time', 'device_public_key', 'device_signature']);

        $this->assertDatabaseCount('license_activations', 0);
    }

    public function test_downgrade_rejects_excess_devices_on_check_and_reactivation_with_409(): void
    {
        $license = License::factory()->create(['max_devices' => 2]);
        $this->activate($license)->assertOk();
        $this->activate($license, self::DEVICE_B)->assertOk();
        $license->update(['max_devices' => 1]);

        $this->check($license)->assertOk();
        $response = $this->check($license, self::DEVICE_B);
        $response->assertStatus(409);
        $this->assertSame('device_limit_reached', $this->payload($response)['error']);
        $this->activate($license, self::DEVICE_B)->assertStatus(409);
        $this->assertSame(2, $license->activations()->count());

        $license->update(['max_devices' => 2]);
        $this->check($license, self::DEVICE_B)->assertOk();
    }

    public function test_unbinding_allowed_device_releases_slot_for_next_existing_device(): void
    {
        $license = License::factory()->create(['max_devices' => 2]);
        $this->activate($license)->assertOk();
        $this->activate($license, self::DEVICE_B)->assertOk();
        $license->update(['max_devices' => 1]);
        $license->activations()->where('device_id', self::DEVICE)->firstOrFail()->delete();

        $this->check($license, self::DEVICE_B)->assertOk();
        $this->activate($license, self::DEVICE_C)->assertStatus(409);
    }

    public function test_a_new_key_cannot_reuse_an_existing_device_slot(): void
    {
        $license = License::factory()->create(['max_devices' => 1]);
        $this->activate($license)->assertOk();
        $originalPublicKey = $license->activations()->sole()->device_public_key;
        unset($this->deviceKeys[self::DEVICE]);

        $this->assertSame('device_key_mismatch', $this->payload($this->check($license)->assertForbidden())['error']);
        $this->assertSame('device_key_mismatch', $this->payload($this->activate($license)->assertForbidden())['error']);
        $this->assertSame($originalPublicKey, $license->activations()->sole()->device_public_key);
        $this->assertSame(1, $license->activations()->count());
    }

    public function test_tampered_device_proof_is_rejected_before_activation(): void
    {
        $license = License::factory()->create();
        $data = $this->signedRequest('activate', $license->key, self::DEVICE);
        $data['device_name'] = 'Changed after signing';

        $response = $this->postJson('/api/v1/license/activate', $data)->assertForbidden();

        $this->assertSame('invalid_device_proof', $this->payload($response)['error']);
        $this->assertDatabaseCount('license_activations', 0);
    }

    public function test_device_proof_cannot_be_used_for_a_different_action(): void
    {
        $license = License::factory()->create();
        $data = $this->signedRequest('check', $license->key, self::DEVICE);

        $response = $this->postJson('/api/v1/license/activate', $data)->assertForbidden();

        $this->assertSame('invalid_device_proof', $this->payload($response)['error']);
        $this->assertDatabaseCount('license_activations', 0);
    }

    public function test_replayed_device_proof_is_rejected(): void
    {
        $license = License::factory()->create();
        $data = $this->signedRequest('activate', $license->key, self::DEVICE);

        $this->postJson('/api/v1/license/activate', $data)->assertOk();
        $response = $this->postJson('/api/v1/license/activate', $data)->assertForbidden();

        $this->assertSame('invalid_device_proof', $this->payload($response)['error']);
        $this->assertSame(1, $license->activations()->count());
    }

    public function test_stale_device_proof_is_rejected(): void
    {
        $license = License::factory()->create();
        $data = $this->signedRequest('activate', $license->key, self::DEVICE, [
            'request_time' => now()->subMinutes(6)->timestamp,
        ]);

        $response = $this->postJson('/api/v1/license/activate', $data)->assertForbidden();

        $this->assertSame('invalid_device_proof', $this->payload($response)['error']);
        $this->assertDatabaseCount('license_activations', 0);
    }

    public function test_existing_activation_can_bind_a_device_key_once(): void
    {
        $license = License::factory()->create();
        $license->activations()->create(['device_id' => self::DEVICE]);

        $this->check($license)->assertOk();

        $this->assertSame(base64_encode(sodium_crypto_sign_publickey($this->deviceKeys[self::DEVICE])),
            $license->activations()->sole()->device_public_key);
        $this->activate($license)->assertOk();
    }
}
