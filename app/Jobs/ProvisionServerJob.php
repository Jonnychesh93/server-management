<?php

namespace App\Jobs;

use App\Enums\ServerConnectionStatus;
use App\Enums\ServerProvisioningStatus;
use App\Events\ServerProvisioningFinished;
use App\Events\ServerProvisioningOutputReceived;
use App\Models\ActivityLog;
use App\Models\Server;
use App\Services\Provisioning\ProvisioningStep;
use App\Services\Provisioning\Steps\ConfigureSwap;
use App\Services\Provisioning\Steps\CreateDeployUser;
use App\Services\Provisioning\Steps\InstallCertbot;
use App\Services\Provisioning\Steps\InstallComposer;
use App\Services\Provisioning\Steps\InstallFirewall;
use App\Services\Provisioning\Steps\InstallMysql;
use App\Services\Provisioning\Steps\InstallNginx;
use App\Services\Provisioning\Steps\InstallNode;
use App\Services\Provisioning\Steps\InstallPhp;
use App\Services\Provisioning\Steps\InstallRedis;
use App\Services\Provisioning\Steps\InstallSupervisor;
use App\Services\Provisioning\Steps\UpdatePackages;
use App\Services\Ssh\OutputRelay;
use App\Services\Ssh\SshConnector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProvisionServerJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public int $tries = 1;

    /**
     * Ordered provisioning steps, run over a single open connection.
     *
     * @var array<int, class-string<ProvisioningStep>>
     */
    private const STEPS = [
        ConfigureSwap::class,
        UpdatePackages::class,
        CreateDeployUser::class,
        InstallFirewall::class,
        InstallNginx::class,
        InstallPhp::class,
        InstallComposer::class,
        InstallNode::class,
        InstallMysql::class,
        InstallRedis::class,
        InstallSupervisor::class,
        InstallCertbot::class,
    ];

    public function __construct(public readonly Server $server)
    {
        $this->onQueue('ssh');
    }

    /**
     * Execute the job.
     */
    public function handle(SshConnector $connector): void
    {
        $this->server->forceFill(['provisioning_status' => ServerProvisioningStatus::Connecting])->save();

        try {
            // A previous attempt may have already generated and installed
            // our own key — reuse it instead of re-authenticating with the
            // original bootstrap credential, which a prior attempt may have
            // already invalidated (CreateDeployUser disables SSH password
            // authentication once it runs).
            $connection = $this->server->ssh_private_key
                ? $connector->connectAsRoot($this->server)
                : $connector->bootstrap($this->server)->installControlPlaneKey();
        } catch (Throwable $e) {
            $this->recordFailure('connecting', $e->getMessage());

            return;
        }

        $this->server->forceFill(['provisioning_status' => ServerProvisioningStatus::Installing])->save();

        $relay = new OutputRelay(
            persist: fn (string $chunk) => $this->server->appendOutput('provisioning_output', $chunk),
            broadcast: function (string $chunk, int $sequence): void {
                broadcast(new ServerProvisioningOutputReceived(
                    $this->server->team_id,
                    $this->server->id,
                    $chunk,
                    $sequence,
                ));
            },
        );

        foreach (self::STEPS as $stepClass) {
            $step = new $stepClass;

            $relay(">>> {$step->name()}\n");

            try {
                $result = $connection->run($step->script($this->server), $relay);
            } catch (Throwable $e) {
                $this->recordFailure($step->name(), $e->getMessage());

                return;
            }

            if (! $result->successful()) {
                $this->recordFailure($step->name(), "Command exited with status {$result->exitCode}.");

                return;
            }
        }

        $this->server->forceFill([
            'ssh_user' => 'appuser',
            'provisioning_status' => ServerProvisioningStatus::Active,
            'connection_status' => ServerConnectionStatus::Online,
            'last_heartbeat_at' => now(),
            'installed_php_versions' => [InstallPhp::DEFAULT_VERSION],
        ])->save();

        broadcast(new ServerProvisioningFinished(
            $this->server->team_id,
            $this->server->id,
            ServerProvisioningStatus::Active,
        ));

        ActivityLog::record(
            $this->server->team,
            null,
            $this->server,
            'server.provisioning.completed',
            "Server \"{$this->server->name}\" finished provisioning.",
        );
    }

    /**
     * Record a provisioning failure, preserving output gathered so far.
     */
    private function recordFailure(string $step, string $message): void
    {
        $this->server->appendOutput('provisioning_output', "\n!!! Failed at [{$step}]: {$message}\n");

        $this->server->forceFill([
            'provisioning_status' => ServerProvisioningStatus::Failed,
            'provisioning_failed_step' => $step,
        ])->save();

        broadcast(new ServerProvisioningFinished(
            $this->server->team_id,
            $this->server->id,
            ServerProvisioningStatus::Failed,
        ));

        ActivityLog::record(
            $this->server->team,
            null,
            $this->server,
            'server.provisioning.failed',
            "Server \"{$this->server->name}\" failed provisioning at step [{$step}].",
        );
    }
}
