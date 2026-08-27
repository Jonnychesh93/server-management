<?php

namespace Database\Factories;

use App\Models\Database;
use App\Models\Server;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Database>
 */
class DatabaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'team_id' => Team::factory(),
            'server_id' => Server::factory(),
            'name' => $name,
            'username' => $name,
            'password' => Str::random(32),
        ];
    }
}
