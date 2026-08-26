<?php

namespace Database\Factories;

use App\Models\GithubInstallation;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GithubInstallation>
 */
class GithubInstallationFactory extends Factory
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
            'installation_id' => fake()->unique()->numberBetween(1000, 999999),
            'account_login' => fake()->userName(),
            'account_type' => 'User',
        ];
    }
}
