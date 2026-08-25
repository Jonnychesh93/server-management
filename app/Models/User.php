<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\TeamRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property int|null $current_team_id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Get the teams this user belongs to.
     *
     * @return BelongsToMany<Team, $this, TeamUser>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)
            ->using(TeamUser::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get the user's currently selected team.
     *
     * @return BelongsTo<Team, $this>
     */
    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    /**
     * Determine if this user belongs to the given team.
     */
    public function belongsToTeam(Team $team): bool
    {
        return $this->teams()->whereKey($team->id)->exists();
    }

    /**
     * Get this user's role on the given team.
     */
    public function roleOn(Team $team): ?TeamRole
    {
        $pivot = $this->teams()->whereKey($team->id)->first()?->pivot;

        return $pivot instanceof TeamUser ? $pivot->role : null;
    }

    /**
     * Determine if this user can manage servers, sites, and membership on the given team.
     */
    public function canManage(Team $team): bool
    {
        return $this->roleOn($team)?->canManage() ?? false;
    }

    /**
     * Switch this user's current team, verifying membership first.
     */
    public function switchTeam(Team $team): void
    {
        if (! $this->belongsToTeam($team)) {
            return;
        }

        $this->forceFill(['current_team_id' => $team->id])->save();
    }
}
