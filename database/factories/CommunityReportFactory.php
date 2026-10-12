<?php

namespace Database\Factories;

use App\Models\CommunityReport;
use App\Models\CommunityTopic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunityReport>
 */
class CommunityReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['community_topic_id' => CommunityTopic::factory(), 'reason' => fake()->sentence(), 'is_processed' => false];
    }
}
