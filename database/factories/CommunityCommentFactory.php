<?php

namespace Database\Factories;

use App\Models\CommunityComment;
use App\Models\CommunityTopic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunityComment>
 */
class CommunityCommentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'community_topic_id' => CommunityTopic::factory(),
            'author_name' => fake()->firstName(),
            'body' => fake()->sentence(),
            'is_staff' => false,
            'status' => CommunityComment::STATUS_PUBLISHED,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => CommunityComment::STATUS_PENDING]);
    }
}
