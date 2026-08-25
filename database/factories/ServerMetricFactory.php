<?php

namespace Database\Factories;

use App\Models\Server;
use App\Models\ServerMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServerMetric>
 */
class ServerMetricFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'server_id' => Server::factory(),
            'cpu' => fake()->numberBetween(1, 100),
            'memory' => fake()->numberBetween(1, 100),
            'disk' => fake()->numberBetween(1, 100),
            'load_1m' => fake()->randomFloat(2, 0, 4),
            'recorded_at' => now(),
        ];
    }
}
