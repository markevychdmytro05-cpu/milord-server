<?php

namespace App\Support;

use App\Models\License;
use Illuminate\Support\Facades\Cache;

final class LicenseRequestProof
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function verify(array $data, string $action): bool
    {
        if (abs(now()->timestamp - (int) $data['request_time']) > 300) {
            return false;
        }

        $publicKey = base64_decode($data['device_public_key'], true);
        $signature = base64_decode($data['device_signature'], true);

        if ($publicKey === false || strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
            || base64_encode($publicKey) !== $data['device_public_key']
            || $signature === false || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES
            || base64_encode($signature) !== $data['device_signature']) {
            return false;
        }

        $message = implode("\n", [
            'license-v2',
            $action,
            License::normalizeKey($data['key']),
            $data['device_id'],
            $data['nonce'],
            (string) $data['request_time'],
            $data['device_public_key'],
            $data['device_name'] ?? '',
            $data['app_version'] ?? '',
        ]);

        if (! sodium_crypto_sign_verify_detached($signature, $message, $publicKey)) {
            return false;
        }

        return Cache::add('license:proof:'.hash('sha256', $data['device_public_key'].$data['nonce']), true, 600);
    }
}
