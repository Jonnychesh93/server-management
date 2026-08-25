<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\Server;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
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
            'user_id' => User::factory(),
            'subject_type' => Server::class,
            'subject_id' => Server::factory(),
            'action' => 'created',
            'description' => fake()->sentence(),
            'metadata' => [],
        ];
    }
}
