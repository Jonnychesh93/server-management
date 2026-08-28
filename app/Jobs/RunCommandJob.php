<?php

namespace App\Jobs;

use App\Enums\CommandStatus;
use App\Events\CommandFinished;
use App\Events\CommandOutputReceived;
use App\Models\Command;
use App\Models\Site;
use App\Services\Provisioning\PhpVersionShim;
use App\Services\Ssh\OutputRelay;
use App\Services\Ssh\SshConnector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RunCommandJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(public readonly Command $command)
    {
        $this->onQueue('ssh');
    }

    /**
     * Execute the job.
     */
    public function handle(SshConnector $connector): void
    {
        $site = $this->command->site;

        $this->command->forceFill([
            'status' => CommandStatus::Running,
            'started_at' => now(),
        ])->save();

        $relay = new OutputRelay(
            persist: fn (string $chunk) => $this->command->appendOutput('output', $chunk),
            broadcast: function (string $chunk, int $sequence): void {
                broadcast(new CommandOutputReceived(
                    $this->command->team_id,
                    $this->command->id,
                    $chunk,
                    $sequence,
                ));
            },
        );

        try {
            $connection = $connector->connect($site->server);
            $result = $connection->run($this->script($site), $relay);

            $this->command->forceFill([
                'status' => $result->successful() ? CommandStatus::Success : CommandStatus::Failed,
                'exit_code' => $result->exitCode,
                'finished_at' => now(),
            ])->save();

            broadcast(new CommandFinished($this->command->team_id, $this->command->id, $this->command->status));
        } catch (Throwable $e) {
            $this->command->appendOutput('output', "\n!!! {$e->getMessage()}\n");

            $this->command->forceFill([
                'status' => CommandStatus::Failed,
                'finished_at' => now(),
            ])->save();

            broadcast(new CommandFinished($this->command->team_id, $this->command->id, CommandStatus::Failed));
        }
    }

    /**
     * Run the command from the site's currently active release, shimming
     * "php" to the site's own configured version the same way a deploy
     * script does.
     */
    private function script(Site $site): string
    {
        $releasePath = $site->remotePath().'/current';
        $shim = PhpVersionShim::script($releasePath, $site->php_version);

        return <<<BASH
            set -e
            {$shim}
            cd {$releasePath}
            {$this->command->command}
            BASH;
    }
}
