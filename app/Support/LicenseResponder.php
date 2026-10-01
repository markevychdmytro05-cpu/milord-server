<?php

namespace App\Support;

use App\Models\License;
use App\Services\LicenseCheckResult;
use Illuminate\Http\JsonResponse;

final class LicenseResponder
{
    public static function respond(LicenseCheckResult $result, string $deviceId, ?string $nonce): JsonResponse
    {
        $payload = [
            'success' => $result->isSuccess(),
            'error' => $result->error,
            'license' => $result->license ? self::licensePayload($result->license) : null,
        ];

        if ($result->isSuccess()) {
            $payload['valid_until'] = $result->validUntil->toIso8601String();
        }

        $payload += [
            'device_id' => $deviceId,
            'nonce' => $nonce,
            'server_time' => now()->toIso8601String(),
        ];

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return JsonResponse::fromJsonString(json_encode([
            'data' => $json,
            'signature' => ResponseSigner::sign($json),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $result->status);
    }

    /**
     * @return array{id: int, max_accounts: int, max_devices: int, expires_at: ?string}
     */
    private static function licensePayload(License $license): array
    {
        return [
            'id' => $license->id,
            'max_accounts' => $license->max_accounts,
            'max_devices' => $license->max_devices,
            'expires_at' => $license->expires_at?->toIso8601String(),
        ];
    }
}
