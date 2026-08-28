<?php

namespace Database\Factories;

use App\Models\Command;
use App\Models\Site;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Command>
 */
class CommandFactory extends Factory
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
            'command' => 'php artisan about',
            'status' => 'queued',
        ];
    }
}
