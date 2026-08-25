<?php

namespace App\Jobs;

use App\Models\Server;
use App\Services\Ssh\SshConnector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RemoveCronJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public int $tries = 1;

    /**
     * Takes the server and the cron's filename directly rather than the Cron
     * model, since this runs after the record has already been deleted.
     */
    public function __construct(public readonly Server $server, public readonly string $filename)
    {
        $this->onQueue('ssh');
    }

    /**
     * Execute the job.
     */
    public function handle(SshConnector $connector): void
    {
        $connector->connectAsRoot($this->server)->run("rm -f /etc/cron.d/{$this->filename}");
    }
}
