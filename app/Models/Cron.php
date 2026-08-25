<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Enums\CronStatus;
use Database\Factories\CronFactory;
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
 * @property string $user
 * @property string $schedule
 * @property CronStatus $status
 * @property Carbon|null $last_synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['team_id', 'server_id', 'command', 'user', 'schedule'])]
class Cron extends Model
{
    use BelongsToTeam;

    /** @use HasFactory<CronFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CronStatus::class,
            'last_synced_at' => 'datetime',
        ];
    }

    /**
     * Get the server this cron job runs on.
     *
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * The filename this cron job is written to under /etc/cron.d/.
     */
    public function filename(): string
    {
        return "cron-{$this->id}";
    }
}
