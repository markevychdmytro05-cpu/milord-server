<?php

namespace Database\Factories;

use App\Models\CommunityTopic;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommunityTopicFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => 'question',
            'title' => fake()->unique()->sentence(4),
            'body' => fake()->paragraph(),
            'author_name' => fake()->firstName(),
            'is_staff' => false,
            'is_pinned' => false,
            'is_members_only' => true,
            'status' => CommunityTopic::STATUS_PUBLISHED,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => CommunityTopic::STATUS_PENDING]);
    }

    public function staff(): static
    {
        return $this->state(['is_staff' => true, 'author_name' => 'Команда Numis']);
    }

    public function open(): static
    {
        return $this->state(['is_members_only' => false]);
    }
}
