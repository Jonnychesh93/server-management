<?php

namespace Database\Factories;

use App\Enums\DeploymentTriggerType;
use App\Models\Deployment;
use App\Models\Site;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deployment>
 */
class DeploymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'site_id' => Site::factory(),
            'status' => 'queued',
            'triggered_by_type' => DeploymentTriggerType::User,
        ];
    }
}
