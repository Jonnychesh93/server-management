<?php

namespace Database\Factories;

use App\Models\Server;
use App\Models\Site;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
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
            'server_id' => Server::factory(),
            'domain' => fake()->unique()->domainName(),
            'document_root' => '/public',
            'php_version' => '8.3',
            'deploy_script' => Site::DEFAULT_DEPLOY_SCRIPT,
        ];
    }

    /**
     * Indicate that the site has finished provisioning and is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }
}
