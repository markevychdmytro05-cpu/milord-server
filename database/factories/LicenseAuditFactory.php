<?php

namespace Database\Factories;

use App\Models\License;
use App\Models\LicenseAudit;
use Illuminate\Database\Eloquent\Factories\Factory;

class LicenseAuditFactory extends Factory
{
    public function definition(): array
    {
        return [
            'license_id' => License::factory(),
            'license_number' => fn (array $attributes): int => $attributes['license_id'],
            'actor_name' => 'Система / API',
            'event' => 'updated',
            'changes' => ['status' => ['old' => 'active', 'new' => 'revoked']],
            'metadata' => [],
        ];
    }
}
