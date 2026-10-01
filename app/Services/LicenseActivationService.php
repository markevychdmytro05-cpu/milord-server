<?php

namespace App\Services;

use App\Models\License;
use App\Models\LicenseActivation;
use App\Repositories\LicenseRepository;
use Illuminate\Support\Carbon;

class LicenseActivationService
{
    public function __construct(private readonly LicenseRepository $licenses) {}

    /** @param  array<string, mixed>  $data */
    public function activate(array $data, ?string $ip): LicenseCheckResult
    {
        return (function () use ($data, $ip): LicenseCheckResult {
            $license = $this->licenses->findByKeyForUpdate($data['key']);

            if ($error = $this->licenseError($license)) {
                return $error;
            }

            $activation = $this->licenses->findActivation($license, $data['device_id']);

            if (! $activation && $this->licenses->countActivations($license) >= $license->max_devices) {
                return LicenseCheckResult::fail('device_limit_reached', 409);
            }

            if ($activation && ! $license->allowsActivation($activation)) {
                return LicenseCheckResult::fail('device_limit_reached', 409);
            }

            $activation ??= $this->licenses->makeActivation($license, $data['device_id']);

            return $this->touchAndRespond($license, $activation, $data, $ip);
        })();
    }

    /** @param  array<string, mixed>  $data */
    public function check(array $data, ?string $ip): LicenseCheckResult
    {
        return (function () use ($data, $ip): LicenseCheckResult {
            $license = $this->licenses->findByKeyForUpdate($data['key']);

            if ($error = $this->licenseError($license)) {
                return $error;
            }

            $activation = $this->licenses->findActivation($license, $data['device_id']);

            if (! $activation) {
                return LicenseCheckResult::fail('device_not_activated', 403);
            }

            if (! $license->allowsActivation($activation)) {
                return LicenseCheckResult::fail('device_limit_reached', 409);
            }

            return $this->touchAndRespond($license, $activation, $data, $ip);
        })();
    }

    private function licenseError(?License $license): ?LicenseCheckResult
    {
        if (! $license) {
            return LicenseCheckResult::fail('license_not_found', 404);
        }

        if ($reason = $license->invalidReason()) {
            return LicenseCheckResult::fail($reason, 403, $license);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function touchAndRespond(License $license, LicenseActivation $activation, array $data, ?string $ip): LicenseCheckResult
    {
        $this->licenses->saveActivation($activation, [
            'device_name' => $data['device_name'] ?? $activation->device_name,
            'app_version' => $data['app_version'] ?? $activation->app_version,
            'accounts_used' => $data['accounts_used'] ?? $activation->accounts_used,
            'ip' => $ip,
            'last_seen_at' => now(),
        ]);

        if ($activation->accounts_used !== null && $activation->accounts_used > $license->max_accounts) {
            return LicenseCheckResult::fail('accounts_limit_exceeded', 403, $license);
        }

        return LicenseCheckResult::ok($license, $this->validUntil($license));
    }

    private function validUntil(License $license): Carbon
    {
        $offline = now()->addHours(config('license.offline_grace_hours'));

        return $license->expires_at && $license->expires_at->lt($offline) ? $license->expires_at : $offline;
    }
}
