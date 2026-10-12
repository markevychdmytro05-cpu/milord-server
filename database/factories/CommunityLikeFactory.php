<?php

namespace Database\Factories;

use App\Models\CommunityLike;
use App\Models\CommunityTopic;
use App\Models\License;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunityLike>
 */
class CommunityLikeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['community_topic_id' => CommunityTopic::factory(), 'license_id' => License::factory()];
    }
}
