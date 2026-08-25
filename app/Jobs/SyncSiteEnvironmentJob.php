<?php

namespace App\Jobs;

use App\Models\Site;
use App\Services\Ssh\SshConnector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncSiteEnvironmentJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public int $tries = 1;

    public function __construct(public readonly Site $site)
    {
        $this->onQueue('ssh');
    }

    /**
     * Execute the job.
     */
    public function handle(SshConnector $connector): void
    {
        $connector->connect($this->site->server)->writeFile(
            $this->site->remotePath().'/shared/.env',
            (string) $this->site->env_encrypted,
            0600,
        );
    }
}
