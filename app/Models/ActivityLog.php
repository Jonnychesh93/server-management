<?php

namespace App\Models;

use Database\Factories\ActivityLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int|null $user_id
 * @property string $subject_type
 * @property int $subject_id
 * @property string $action
 * @property string $description
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 */
#[Fillable(['team_id', 'user_id', 'subject_type', 'subject_id', 'action', 'description', 'metadata'])]
class ActivityLog extends Model
{
    /** @use HasFactory<ActivityLogFactory> */
    use HasFactory;

    protected $table = 'activity_log';

    const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the team this activity belongs to.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user who performed this activity, if any.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the model this activity concerns.
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Record a team activity entry.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function record(
        Team $team,
        ?User $user,
        Model $subject,
        string $action,
        string $description,
        array $metadata = [],
    ): self {
        return static::create([
            'team_id' => $team->id,
            'user_id' => $user?->id,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}
