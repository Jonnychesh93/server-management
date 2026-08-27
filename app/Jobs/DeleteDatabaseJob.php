<?php

namespace App\Jobs;

use App\Models\Server;
use App\Services\Ssh\SshConnector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeleteDatabaseJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public int $tries = 1;

    /**
     * Takes the server and the database's name/username directly rather than
     * the Database model, since this runs after the record has already been
     * deleted.
     */
    public function __construct(
        public readonly Server $server,
        public readonly string $name,
        public readonly string $username,
    ) {
        $this->onQueue('ssh');
    }

    /**
     * Execute the job.
     */
    public function handle(SshConnector $connector): void
    {
        $connector->connectAsRoot($this->server)->run(<<<BASH
            set -e
            mysql -e "DROP DATABASE IF EXISTS \`{$this->name}\`; DROP USER IF EXISTS '{$this->username}'@'localhost'; FLUSH PRIVILEGES;"
            BASH);
    }
}
