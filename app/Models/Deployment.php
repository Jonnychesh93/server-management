<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Concerns\HasStreamedOutput;
use App\Enums\DeploymentStatus;
use App\Enums\DeploymentTriggerType;
use Database\Factories\DeploymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int $site_id
 * @property DeploymentStatus $status
 * @property DeploymentTriggerType $triggered_by_type
 * @property int|null $triggered_by_user_id
 * @property string|null $commit_sha
 * @property string|null $commit_message
 * @property string|null $output
 * @property string|null $failed_step
 * @property int|null $exit_code
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['team_id', 'site_id', 'status', 'triggered_by_type', 'triggered_by_user_id'])]
class Deployment extends Model
{
    use BelongsToTeam, HasStreamedOutput;

    /** @use HasFactory<DeploymentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DeploymentStatus::class,
            'triggered_by_type' => DeploymentTriggerType::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * Get the site this deployment belongs to.
     *
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the user who triggered this deployment, if any.
     *
     * @return BelongsTo<User, $this>
     */
    public function triggeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }
}
