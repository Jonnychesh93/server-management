<?php

namespace App\Models;

use App\Enums\TeamRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property TeamRole $role
 */
class TeamUser extends Pivot
{
    /**
     * The table associated with the pivot model.
     */
    protected $table = 'team_user';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => TeamRole::class,
        ];
    }
}
