<?php

namespace App\Repositories;

use App\Models\License;
use App\Models\LicenseActivation;

class LicenseRepository
{
    public function findByKeyForUpdate(string $key): ?License
    {
        return License::where('key', License::normalizeKey($key))->lockForUpdate()->first();
    }

    public function findActivation(License $license, string $deviceId): ?LicenseActivation
    {
        return $license->activations()->where('device_id', $deviceId)->first();
    }

    public function countActivations(License $license): int
    {
        return $license->activations()->count();
    }

    public function makeActivation(License $license, string $deviceId): LicenseActivation
    {
        return $license->activations()->make(['device_id' => $deviceId]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function saveActivation(LicenseActivation $activation, array $attributes): LicenseActivation
    {
        $activation->fill($attributes)->save();

        return $activation;
    }
}
