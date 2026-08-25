<?php

namespace App\Jobs;

use App\Enums\SslStatus;
use App\Models\ActivityLog;
use App\Models\Site;
use App\Services\Ssh\SshConnector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class IssueSslCertificateJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 180;

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
        try {
            $result = $connector->connectAsRoot($this->site->server)->run($this->certbotScript());

            if (! $result->successful()) {
                $this->recordFailure("Command exited with status {$result->exitCode}.");

                return;
            }

            // Let's Encrypt certificates are valid for 90 days; certbot's own
            // package installs a renewal timer on the box, this column is
            // informational for the dashboard rather than driving renewal.
            $this->site->forceFill([
                'ssl_status' => SslStatus::Active,
                'ssl_certificate_expires_at' => now()->addDays(89),
            ])->save();

            ActivityLog::record(
                $this->site->team,
                null,
                $this->site,
                'site.ssl.issued',
                "SSL certificate issued for \"{$this->site->domain}\".",
            );
        } catch (Throwable $e) {
            $this->recordFailure($e->getMessage());
        }
    }

    private function certbotScript(): string
    {
        $domain = $this->site->domain;

        return <<<BASH
            set -e
            certbot --nginx -d {$domain} --non-interactive --agree-tos --register-unsafely-without-email --redirect
            BASH;
    }

    private function recordFailure(string $message): void
    {
        $this->site->forceFill(['ssl_status' => SslStatus::Failed])->save();

        ActivityLog::record(
            $this->site->team,
            null,
            $this->site,
            'site.ssl.failed',
            "Failed to issue an SSL certificate for \"{$this->site->domain}\": {$message}",
        );
    }
}
