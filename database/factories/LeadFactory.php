<?php

namespace Database\Factories;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'contact' => fake()->e164PhoneNumber(),
            'message' => fake()->sentence(),
            'is_processed' => false,
        ];
    }
}
