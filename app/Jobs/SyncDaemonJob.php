<?php

namespace App\Jobs;

use App\Enums\DaemonStatus;
use App\Models\Daemon;
use App\Services\Provisioning\SupervisorDaemonConfig;
use App\Services\Ssh\SshConnector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SyncDaemonJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public int $tries = 1;

    public function __construct(public readonly Daemon $daemon)
    {
        $this->onQueue('ssh');
    }

    /**
     * Execute the job.
     */
    public function handle(SshConnector $connector): void
    {
        try {
            $root = $connector->connectAsRoot($this->daemon->server);

            $root->writeFile(
                "/etc/supervisor/conf.d/{$this->daemon->slug()}.conf",
                SupervisorDaemonConfig::render($this->daemon),
            );

            $result = $root->run(<<<'BASH'
                set -e
                supervisorctl reread
                supervisorctl update
                BASH);

            $this->daemon->forceFill([
                'status' => $result->successful() ? DaemonStatus::Active : DaemonStatus::Failed,
                'last_synced_at' => now(),
            ])->save();
        } catch (Throwable) {
            $this->daemon->forceFill(['status' => DaemonStatus::Failed])->save();
        }
    }
}
