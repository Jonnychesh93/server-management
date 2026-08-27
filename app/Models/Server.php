<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Concerns\HasStreamedOutput;
use App\Enums\BootstrapCredentialType;
use App\Enums\ServerConnectionStatus;
use App\Enums\ServerOs;
use App\Enums\ServerProvisioningStatus;
use Database\Factories\ServerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property string $name
 * @property string $ip_address
 * @property int $ssh_port
 * @property string $ssh_user
 * @property ServerOs $os
 * @property string|null $ssh_private_key
 * @property string|null $ssh_public_key
 * @property string|null $bootstrap_credential
 * @property BootstrapCredentialType|null $bootstrap_credential_type
 * @property ServerProvisioningStatus $provisioning_status
 * @property string|null $provisioning_failed_step
 * @property string|null $provisioning_output
 * @property ServerConnectionStatus $connection_status
 * @property Carbon|null $last_heartbeat_at
 * @property int|null $cpu_usage
 * @property int|null $memory_usage
 * @property int|null $disk_usage
 * @property array<int, string>|null $installed_php_versions
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['team_id', 'name', 'ip_address', 'ssh_port', 'ssh_user', 'os', 'bootstrap_credential', 'bootstrap_credential_type'])]
#[Hidden(['ssh_private_key', 'bootstrap_credential'])]
class Server extends Model
{
    use BelongsToTeam, HasStreamedOutput;

    /** @use HasFactory<ServerFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'os' => ServerOs::class,
            'ssh_private_key' => 'encrypted',
            'bootstrap_credential' => 'encrypted',
            'bootstrap_credential_type' => BootstrapCredentialType::class,
            'provisioning_status' => ServerProvisioningStatus::class,
            'connection_status' => ServerConnectionStatus::class,
            'last_heartbeat_at' => 'datetime',
            'installed_php_versions' => 'array',
        ];
    }

    /**
     * Get the sites hosted on this server.
     *
     * @return HasMany<Site, $this>
     */
    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    /**
     * Get the daemons running on this server.
     *
     * @return HasMany<Daemon, $this>
     */
    public function daemons(): HasMany
    {
        return $this->hasMany(Daemon::class);
    }

    /**
     * Get the cron jobs scheduled on this server.
     *
     * @return HasMany<Cron, $this>
     */
    public function crons(): HasMany
    {
        return $this->hasMany(Cron::class);
    }

    /**
     * Get the MySQL databases created on this server.
     *
     * @return HasMany<Database, $this>
     */
    public function databases(): HasMany
    {
        return $this->hasMany(Database::class);
    }
}
