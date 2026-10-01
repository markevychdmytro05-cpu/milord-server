<?php

namespace Database\Factories;

use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'max_accounts' => 5,
            'max_devices' => 1,
            'price' => 100,
            'duration_days' => 30,
            'is_active' => true,
        ];
    }

    public function lifetime(): static
    {
        return $this->state(fn (array $attributes) => ['duration_days' => null]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
