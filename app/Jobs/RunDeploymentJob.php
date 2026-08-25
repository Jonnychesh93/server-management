<?php

namespace App\Jobs;

use App\Enums\DeploymentStatus;
use App\Events\DeploymentFinished;
use App\Events\DeploymentOutputReceived;
use App\Models\Deployment;
use App\Models\Site;
use App\Services\Git\GitConnectionProvider;
use App\Services\Git\GitConnectionProviderFactory;
use App\Services\Ssh\OutputRelay;
use App\Services\Ssh\SshConnector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RunDeploymentJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(public readonly Deployment $deployment)
    {
        $this->onQueue('ssh');
    }

    /**
     * Execute the job.
     */
    public function handle(SshConnector $connector): void
    {
        $site = $this->deployment->site;

        $this->deployment->forceFill([
            'status' => DeploymentStatus::Running,
            'started_at' => now(),
        ])->save();

        $relay = new OutputRelay(
            persist: fn (string $chunk) => $this->deployment->appendOutput('output', $chunk),
            broadcast: function (string $chunk, int $sequence): void {
                broadcast(new DeploymentOutputReceived(
                    $this->deployment->team_id,
                    $this->deployment->id,
                    $chunk,
                    $sequence,
                ));
            },
        );

        try {
            $gitConnection = $site->gitConnection;

            if (! $gitConnection) {
                $this->recordFailure('resolve_repository', 'This site has no git repository connected.');

                return;
            }

            $provider = GitConnectionProviderFactory::for($gitConnection);
            $connection = $connector->connect($site->server);
            $releasePath = $site->remotePath().'/releases/'.now()->format('Ymd_His');

            if ($privateKey = $provider->deployPrivateKey()) {
                $connection->writeFile($site->remotePath().'/shared/deploy_key', $privateKey, 0600);
            }

            $relay(">>> cloning {$provider->repository()}\n");
            $result = $connection->run($this->cloneScript($releasePath, $site, $provider), $relay);

            if (! $result->successful()) {
                $this->recordFailure('clone_repository', "Command exited with status {$result->exitCode}.");

                return;
            }

            $this->captureCommit($result->output);

            $relay(">>> running deploy script\n");
            $result = $connection->run($this->deployScript($releasePath, $site), $relay);

            if (! $result->successful()) {
                $this->recordFailure('run_deploy_script', "Command exited with status {$result->exitCode}.");

                return;
            }

            $relay(">>> activating release\n");
            $result = $connection->run($this->activateScript($releasePath, $site), $relay);

            if (! $result->successful()) {
                $this->recordFailure('activate_release', "Command exited with status {$result->exitCode}.");

                return;
            }

            $this->deployment->forceFill([
                'status' => DeploymentStatus::Success,
                'exit_code' => 0,
                'finished_at' => now(),
            ])->save();

            $site->forceFill([
                'last_deployed_at' => now(),
                'last_deployment_id' => $this->deployment->id,
            ])->save();

            broadcast(new DeploymentFinished($this->deployment->team_id, $this->deployment->id, DeploymentStatus::Success));
        } catch (Throwable $e) {
            $this->recordFailure('deployment', $e->getMessage());
        }
    }

    /**
     * Clone the repository into the new release directory over the deploy
     * key (if any), and symlink the shared .env file into place.
     */
    private function cloneScript(string $releasePath, Site $site, GitConnectionProvider $provider): string
    {
        $repository = $provider->repository();
        $branch = $provider->branch();
        $sshCommand = $provider->deployPrivateKey()
            ? "GIT_SSH_COMMAND=\"ssh -i {$site->remotePath()}/shared/deploy_key -o StrictHostKeyChecking=accept-new -o UserKnownHostsFile=/dev/null\" "
            : '';

        return <<<BASH
            set -e
            mkdir -p {$releasePath}
            {$sshCommand}git clone --branch {$branch} --depth 1 {$repository} {$releasePath}
            ln -sfn ../../shared/.env {$releasePath}/.env
            cd {$releasePath}
            echo "DEPLOY_SHA=\$(git rev-parse HEAD)"
            echo "DEPLOY_MSG=\$(git log -1 --pretty=%s)"
            BASH;
    }

    /**
     * Run the site's deploy script from the new release directory.
     */
    private function deployScript(string $releasePath, Site $site): string
    {
        return "cd {$releasePath}\n{$site->deploy_script}";
    }

    /**
     * Atomically point "current" at the new release and prune old releases.
     */
    private function activateScript(string $releasePath, Site $site): string
    {
        $siteRoot = $site->remotePath();

        return <<<BASH
            set -e
            ln -sfn {$releasePath} {$siteRoot}/current
            cd {$siteRoot}/releases && ls -1dt */ | tail -n +6 | xargs -r rm -rf
            BASH;
    }

    /**
     * Pull the commit SHA and message the clone step echoed out.
     */
    private function captureCommit(string $output): void
    {
        if (preg_match('/DEPLOY_SHA=(\S+)/', $output, $shaMatch)) {
            $this->deployment->forceFill(['commit_sha' => $shaMatch[1]])->save();
        }

        if (preg_match('/DEPLOY_MSG=(.*)/', $output, $msgMatch)) {
            $this->deployment->forceFill(['commit_message' => trim($msgMatch[1])])->save();
        }
    }

    /**
     * Record a deployment failure, preserving output gathered so far.
     */
    private function recordFailure(string $step, string $message): void
    {
        $this->deployment->appendOutput('output', "\n!!! Failed at [{$step}]: {$message}\n");

        $this->deployment->forceFill([
            'status' => DeploymentStatus::Failed,
            'failed_step' => $step,
            'finished_at' => now(),
        ])->save();

        broadcast(new DeploymentFinished($this->deployment->team_id, $this->deployment->id, DeploymentStatus::Failed));
    }
}
