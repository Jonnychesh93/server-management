<?php

namespace Database\Factories;

use App\Models\Cron;
use App\Models\Server;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cron>
 */
class CronFactory extends Factory
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
            'command' => 'php /home/appuser/example.com/current/artisan schedule:run',
            'user' => 'appuser',
            'schedule' => '* * * * *',
        ];
    }
}
