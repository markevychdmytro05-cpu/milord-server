<?php

namespace Database\Factories;

use App\Models\License;
use Illuminate\Database\Eloquent\Factories\Factory;

class LicenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer' => fake()->name(),
            'contact' => '@'.fake()->userName(),
            'max_accounts' => 5,
            'max_devices' => 1,
            'status' => License::STATUS_ACTIVE,
            'expires_at' => now()->addMonth(),
        ];
    }

    public function lifetime(): static
    {
        return $this->state(fn (array $attributes) => ['expires_at' => null]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => ['expires_at' => now()->subDay()]);
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => ['status' => License::STATUS_REVOKED]);
    }
}
