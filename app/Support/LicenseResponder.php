<?php

namespace App\Support;

use App\Models\License;
use App\Models\Release;
use App\Services\LicenseCheckResult;
use Illuminate\Http\JsonResponse;

final class LicenseResponder
{
    public static function respond(LicenseCheckResult $result, string $deviceId, string $nonce, string $devicePublicKey, ?Release $update = null): JsonResponse
    {
        $payload = [
            'success' => $result->isSuccess(),
            'error' => $result->error,
            'license' => $result->license ? self::licensePayload($result->license) : null,
        ];

        if ($result->isSuccess()) {
            $payload['valid_until'] = $result->validUntil->toIso8601String();
            $payload['update'] = $update ? self::updatePayload($update) : null;
        }

        $payload += [
            'device_id' => $deviceId,
            'device_public_key' => $devicePublicKey,
            'nonce' => $nonce,
            'server_time' => now()->toIso8601String(),
        ];

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return JsonResponse::fromJsonString(json_encode([
            'data' => $json,
            'signature' => ResponseSigner::sign($json),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $result->status);
    }

    private static function licensePayload(License $license): array
    {
        return [
            'id' => $license->id,
            'max_accounts' => $license->max_accounts,
            'max_devices' => $license->max_devices,
            'expires_at' => $license->expires_at?->toIso8601String(),
        ];
    }

    private static function updatePayload(Release $release): array
    {
        return [
            'version' => $release->version,
            'notes' => $release->notes,
            'size' => $release->size,
            'sha256' => $release->sha256,
            'url' => $release->temporaryUrl(360),
        ];
    }
}
