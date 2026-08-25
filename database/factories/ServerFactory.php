<?php

namespace Database\Factories;

use App\Enums\BootstrapCredentialType;
use App\Enums\ServerOs;
use App\Models\Server;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Server>
 */
class ServerFactory extends Factory
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
            'name' => fake()->unique()->domainWord(),
            'ip_address' => fake()->ipv4(),
            'ssh_port' => 22,
            'ssh_user' => 'root',
            'os' => ServerOs::Ubuntu2404,
            'bootstrap_credential' => fake()->password(20),
            'bootstrap_credential_type' => BootstrapCredentialType::Password,
        ];
    }

    /**
     * Indicate that the server has finished provisioning and is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'provisioning_status' => 'active',
            'connection_status' => 'online',
            'ssh_user' => 'appuser',
            'ssh_private_key' => 'test-private-key',
            'ssh_public_key' => 'ssh-ed25519 AAAA... test-public-key',
            'bootstrap_credential' => null,
            'bootstrap_credential_type' => null,
        ]);
    }
}
