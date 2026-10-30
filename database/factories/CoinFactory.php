<?php

namespace Database\Factories;

use App\Models\Coin;
use Illuminate\Database\Eloquent\Factories\Factory;

class CoinFactory extends Factory
{
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
            'image' => '/uploads/coins/test.jpg',
            'is_published' => true,
            'show_on_home' => true,
        ];
    }

    public function upcoming(): static
    {
        return $this->state(fn () => ['sale_starts_at' => now()->addWeek()]);
    }

    public function withoutImage(): static
    {
        return $this->state(fn () => ['image' => null]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
