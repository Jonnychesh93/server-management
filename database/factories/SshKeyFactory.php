<?php

namespace Database\Factories;

use App\Enums\SshKeyType;
use App\Models\SshKey;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SshKey>
 */
class SshKeyFactory extends Factory
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
            'site_id' => null,
            'name' => fake()->unique()->slug(2),
            'public_key' => 'ssh-ed25519 AAAA... test-public-key',
            'private_key' => 'test-private-key',
            'type' => SshKeyType::DeployKey,
        ];
    }
}
