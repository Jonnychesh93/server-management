<?php

namespace Database\Factories;

use App\Enums\GitProvider;
use App\Models\GitConnection;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GitConnection>
 */
class GitConnectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'provider' => GitProvider::Manual,
            'repository' => 'git@github.com:acme/example.git',
            'branch' => 'main',
        ];
    }
}
