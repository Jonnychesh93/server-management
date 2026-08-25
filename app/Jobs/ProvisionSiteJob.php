<?php

namespace App\Jobs;

use App\Enums\SiteStatus;
use App\Events\SiteProvisioningFinished;
use App\Events\SiteProvisioningOutputReceived;
use App\Models\ActivityLog;
use App\Models\Server;
use App\Models\Site;
use App\Services\Provisioning\NginxSiteConfig;
use App\Services\Provisioning\Steps\InstallPhp;
use App\Services\Ssh\OutputRelay;
use App\Services\Ssh\SshConnection;
use App\Services\Ssh\SshConnector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProvisionSiteJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

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
        $server = $this->site->server;

        $relay = new OutputRelay(
            persist: fn (string $chunk) => $this->site->appendOutput('provisioning_output', $chunk),
            broadcast: function (string $chunk, int $sequence): void {
                broadcast(new SiteProvisioningOutputReceived(
                    $this->site->team_id,
                    $this->site->id,
                    $chunk,
                    $sequence,
                ));
            },
        );

        try {
            // Infrastructure changes the control plane itself initiates (as
            // opposed to a user's deploy script) run as root, using the same
            // keypair root's key-based login was never disabled for.
            $root = $connector->connectAsRoot($server);

            if (! $this->ensurePhpVersionInstalled($server, $root, $relay)) {
                return;
            }

            $relay(">>> creating site directories\n");
            $result = $root->run($this->directorySetupScript($server->ssh_user), $relay);

            if (! $result->successful()) {
                $this->recordFailure('create_directories', "Command exited with status {$result->exitCode}.");

                return;
            }

            $relay(">>> configuring nginx\n");
            $root->writeFile("/etc/nginx/sites-available/{$this->site->domain}", NginxSiteConfig::render($this->site));

            $result = $root->run($this->enableSiteScript(), $relay);

            if (! $result->successful()) {
                $this->recordFailure('configure_nginx', "Command exited with status {$result->exitCode}.");

                return;
            }

            $this->site->forceFill(['status' => SiteStatus::Active])->save();

            broadcast(new SiteProvisioningFinished($this->site->team_id, $this->site->id, SiteStatus::Active));

            ActivityLog::record(
                $this->site->team,
                null,
                $this->site,
                'site.provisioning.completed',
                "Site \"{$this->site->domain}\" is live.",
            );
        } catch (Throwable $e) {
            $this->recordFailure('provisioning', $e->getMessage());
        }
    }

    /**
     * Install the site's requested PHP-FPM version if the server doesn't
     * already have it, recording the server's growing version list.
     */
    private function ensurePhpVersionInstalled(Server $server, SshConnection $root, OutputRelay $relay): bool
    {
        $installed = $server->installed_php_versions ?? [];

        if (in_array($this->site->php_version, $installed, true)) {
            return true;
        }

        $relay(">>> installing php {$this->site->php_version}\n");

        $result = $root->run(InstallPhp::installScriptFor($this->site->php_version), $relay);

        if (! $result->successful()) {
            $this->recordFailure('install_php', "Command exited with status {$result->exitCode}.");

            return false;
        }

        $server->forceFill([
            'installed_php_versions' => [...$installed, $this->site->php_version],
        ])->save();

        return true;
    }

    /**
     * Create the site's release/shared/current directory structure with a
     * placeholder page, then hand ownership to the deploy user.
     */
    private function directorySetupScript(string $sshUser): string
    {
        $root = $this->site->remotePath();

        return <<<BASH
            set -e
            mkdir -p {$root}/releases/initial/public
            mkdir -p {$root}/shared
            cat > {$root}/releases/initial/public/index.php <<'PHP'
            <?php echo 'Site provisioned. Deploy to go live.';
            PHP
            touch {$root}/shared/.env
            chmod 600 {$root}/shared/.env
            ln -sfn releases/initial {$root}/current
            chown -R {$sshUser}:{$sshUser} {$root}
            BASH;
    }

    /**
     * Symlink the vhost into sites-enabled, verify the config, and reload.
     */
    private function enableSiteScript(): string
    {
        $domain = $this->site->domain;

        return <<<BASH
            set -e
            ln -sfn /etc/nginx/sites-available/{$domain} /etc/nginx/sites-enabled/{$domain}
            nginx -t
            systemctl reload nginx
            BASH;
    }

    /**
     * Record a provisioning failure, preserving output gathered so far.
     */
    private function recordFailure(string $step, string $message): void
    {
        $this->site->appendOutput('provisioning_output', "\n!!! Failed at [{$step}]: {$message}\n");

        $this->site->forceFill([
            'status' => SiteStatus::Failed,
            'provisioning_failed_step' => $step,
        ])->save();

        broadcast(new SiteProvisioningFinished($this->site->team_id, $this->site->id, SiteStatus::Failed));

        ActivityLog::record(
            $this->site->team,
            null,
            $this->site,
            'site.provisioning.failed',
            "Site \"{$this->site->domain}\" failed provisioning at step [{$step}].",
        );
    }
}
