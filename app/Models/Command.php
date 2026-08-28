<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Concerns\HasStreamedOutput;
use App\Enums\CommandStatus;
use Database\Factories\CommandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int $team_id
 * @property int $site_id
 * @property int|null $user_id
 * @property string $command
 * @property CommandStatus $status
 * @property string|null $output
 * @property int|null $exit_code
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['team_id', 'site_id', 'user_id', 'command', 'status'])]
class Command extends Model
{
    use BelongsToTeam, HasStreamedOutput, HasUuids;

    /** @use HasFactory<CommandFactory> */
    use HasFactory;

    /**
     * Only generate a UUID for the "uuid" column — "id" stays the normal
     * auto-increment primary key everything else's foreign keys reference.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * Use the UUID (not the sequential id) for route binding, so a
     * command's URLs don't leak how many commands exist or let one be
     * guessed from another.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CommandStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * Get the site this command was run against.
     *
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the user who ran this command, if any.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
