<?php

namespace App\Support;

use RuntimeException;

final class ResponseSigner
{
    public static function sign(string $payload): ?string
    {
        $secret = config('license.signing_secret_key');

        if (! $secret) {
            if (app()->isProduction() || config('license.require_signature')) {
                throw new RuntimeException('License response signing is required but LICENSE_SIGNING_SECRET_KEY is missing.');
            }

            return null;
        }

        if (! function_exists('sodium_crypto_sign_detached')) {
            throw new RuntimeException('The sodium extension is required for license response signing.');
        }

        $secret = base64_decode($secret, true);

        if ($secret === false || strlen($secret) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw new RuntimeException('LICENSE_SIGNING_SECRET_KEY is not a valid base64 Ed25519 secret key.');
        }

        return base64_encode(sodium_crypto_sign_detached($payload, $secret));
    }

    /** @return array{public: string, secret: string} base64 */
    public static function generateKeyPair(): array
    {
        $pair = sodium_crypto_sign_keypair();

        return [
            'public' => base64_encode(sodium_crypto_sign_publickey($pair)),
            'secret' => base64_encode(sodium_crypto_sign_secretkey($pair)),
        ];
    }
}
