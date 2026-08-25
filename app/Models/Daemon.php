<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Enums\DaemonStatus;
use Database\Factories\DaemonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int $server_id
 * @property string $command
 * @property string $directory
 * @property string $user
 * @property int $processes
 * @property DaemonStatus $status
 * @property Carbon|null $last_synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['team_id', 'server_id', 'command', 'directory', 'user', 'processes'])]
class Daemon extends Model
{
    use BelongsToTeam;

    /** @use HasFactory<DaemonFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DaemonStatus::class,
            'last_synced_at' => 'datetime',
        ];
    }

    /**
     * Get the server this daemon runs on.
     *
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * A unique, filesystem-safe identifier for this daemon's supervisor config.
     */
    public function slug(): string
    {
        return "daemon-{$this->id}";
    }
}
