<?php

namespace App\Jobs;

use App\Models\Server;
use App\Services\Ssh\SshConnector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RemoveDaemonJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public int $tries = 1;

    /**
     * Takes the server and the daemon's slug directly rather than the Daemon
     * model, since this runs after the record has already been deleted.
     */
    public function __construct(public readonly Server $server, public readonly string $slug)
    {
        $this->onQueue('ssh');
    }

    /**
     * Execute the job.
     */
    public function handle(SshConnector $connector): void
    {
        $connector->connectAsRoot($this->server)->run(<<<BASH
            set -e
            rm -f /etc/supervisor/conf.d/{$this->slug}.conf
            supervisorctl reread
            supervisorctl update
            BASH);
    }
}
