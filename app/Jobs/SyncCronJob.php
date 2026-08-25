<?php

namespace App\Jobs;

use App\Enums\CronStatus;
use App\Models\Cron;
use App\Services\Ssh\SshConnector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SyncCronJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public int $tries = 1;

    public function __construct(public readonly Cron $cron)
    {
        $this->onQueue('ssh');
    }

    /**
     * Execute the job.
     */
    public function handle(SshConnector $connector): void
    {
        try {
            $line = "{$this->cron->schedule} {$this->cron->user} {$this->cron->command}\n";

            $connector->connectAsRoot($this->cron->server)->writeFile(
                "/etc/cron.d/{$this->cron->filename()}",
                $line,
            );

            $this->cron->forceFill([
                'status' => CronStatus::Active,
                'last_synced_at' => now(),
            ])->save();
        } catch (Throwable) {
            $this->cron->forceFill(['status' => CronStatus::Failed])->save();
        }
    }
}
