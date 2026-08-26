<?php

namespace App\Models;

use App\Enums\TeamRole;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name'])]
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    /**
     * Get the users belonging to this team.
     *
     * @return BelongsToMany<User, $this, TeamUser>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(TeamUser::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get the pending invitations for this team.
     *
     * @return HasMany<TeamInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    /**
     * Get the servers belonging to this team.
     *
     * @return HasMany<Server, $this>
     */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    /**
     * Get this team's linked GitHub App installation, if any.
     *
     * @return HasOne<GithubInstallation, $this>
     */
    public function githubInstallation(): HasOne
    {
        return $this->hasOne(GithubInstallation::class);
    }

    /**
     * Determine if the given user belongs to this team.
     */
    public function hasUser(User $user): bool
    {
        return $this->users()->whereKey($user->id)->exists();
    }

    /**
     * Get the given user's role on this team.
     */
    public function roleFor(User $user): ?TeamRole
    {
        $pivot = $this->users()->whereKey($user->id)->first()?->pivot;

        return $pivot instanceof TeamUser ? $pivot->role : null;
    }
}
