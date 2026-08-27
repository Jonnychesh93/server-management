<?php

namespace App\Jobs;

use App\Enums\DatabaseStatus;
use App\Models\Database;
use App\Services\Ssh\SshConnector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class CreateDatabaseJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public int $tries = 1;

    public function __construct(public readonly Database $database)
    {
        $this->onQueue('ssh');
    }

    /**
     * Execute the job.
     */
    public function handle(SshConnector $connector): void
    {
        try {
            $name = $this->database->name;
            $username = $this->database->username;
            $password = $this->database->password;

            $result = $connector->connectAsRoot($this->database->server)->run(<<<BASH
                set -e
                mysql -e "CREATE DATABASE IF NOT EXISTS \`{$name}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER IF NOT EXISTS '{$username}'@'localhost' IDENTIFIED BY '{$password}'; GRANT ALL PRIVILEGES ON \`{$name}\`.* TO '{$username}'@'localhost'; FLUSH PRIVILEGES;"
                BASH);

            $this->database->forceFill([
                'status' => $result->successful() ? DatabaseStatus::Active : DatabaseStatus::Failed,
                'last_synced_at' => now(),
            ])->save();
        } catch (Throwable) {
            $this->database->forceFill(['status' => DatabaseStatus::Failed])->save();
        }
    }
}
