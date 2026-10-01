<?php

namespace Database\Factories;

use App\Models\Coin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coin>
 */
class CoinFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(3),
            'slug' => fake()->unique()->slug(3),
            'denomination' => '10 грн',
            'release_year' => 2026,
            'mintage' => 5000,
            'price' => 450,
            'excerpt' => fake()->sentence(),
            'content' => '<p>'.fake()->paragraph().'</p>',
            'is_published' => true,
        ];
    }

    public function upcoming(): static
    {
        return $this->state(fn () => ['sale_starts_at' => now()->addWeek()]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
