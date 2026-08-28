<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Concerns\HasStreamedOutput;
use App\Enums\SiteStatus;
use App\Enums\SslStatus;
use Database\Factories\SiteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int $server_id
 * @property string $domain
 * @property string $document_root
 * @property string $php_version
 * @property string $deploy_script
 * @property string|null $env_encrypted
 * @property SiteStatus $status
 * @property string|null $provisioning_failed_step
 * @property string|null $provisioning_output
 * @property SslStatus $ssl_status
 * @property Carbon|null $ssl_certificate_expires_at
 * @property Carbon|null $last_deployed_at
 * @property int|null $last_deployment_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['team_id', 'server_id', 'domain', 'document_root', 'php_version', 'deploy_script', 'env_encrypted'])]
#[Hidden(['env_encrypted'])]
class Site extends Model
{
    use BelongsToTeam, HasStreamedOutput;

    /** @use HasFactory<SiteFactory> */
    use HasFactory;

    /**
     * A sensible starting deploy script, seeded for every new site. Runs
     * with the new release directory as its working directory.
     */
    public const DEFAULT_DEPLOY_SCRIPT = <<<'BASH'
        composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
        php artisan migrate --force
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        php artisan queue:restart
        BASH;

    /**
     * Seeded into a site's .env on its first successful deployment, if the
     * repository has no .env.example of its own to use instead.
     */
    public const DEFAULT_ENV_TEMPLATE = <<<'ENV'
        APP_NAME=Laravel
        APP_ENV=production
        APP_KEY=
        APP_DEBUG=false
        APP_URL=http://localhost

        APP_LOCALE=en
        APP_FALLBACK_LOCALE=en
        APP_FAKER_LOCALE=en_US

        APP_MAINTENANCE_DRIVER=file

        BCRYPT_ROUNDS=12

        LOG_CHANNEL=stack
        LOG_STACK=single
        LOG_LEVEL=debug

        DB_CONNECTION=sqlite

        SESSION_DRIVER=file
        SESSION_LIFETIME=120

        CACHE_STORE=file

        QUEUE_CONNECTION=sync

        MAIL_MAILER=log
        ENV;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'env_encrypted' => 'encrypted',
            'status' => SiteStatus::class,
            'ssl_status' => SslStatus::class,
            'ssl_certificate_expires_at' => 'datetime',
            'last_deployed_at' => 'datetime',
        ];
    }

    /**
     * Get the server this site is hosted on.
     *
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Get this site's git connection.
     *
     * @return HasOne<GitConnection, $this>
     */
    public function gitConnection(): HasOne
    {
        return $this->hasOne(GitConnection::class);
    }

    /**
     * Get the SSH keys generated for this site.
     *
     * @return HasMany<SshKey, $this>
     */
    public function sshKeys(): HasMany
    {
        return $this->hasMany(SshKey::class);
    }

    /**
     * Get this site's deployment history, most recent first.
     *
     * @return HasMany<Deployment, $this>
     */
    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class)->latest();
    }

    /**
     * The absolute path to this site's root directory on the server.
     */
    public function remotePath(): string
    {
        return "/home/{$this->server->ssh_user}/{$this->domain}";
    }
}
