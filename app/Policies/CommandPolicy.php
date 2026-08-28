<?php

namespace App\Policies;

use App\Models\Command;
use App\Models\Site;
use App\Models\User;

class CommandPolicy
{
    /**
     * Determine whether the user can run a command against the site.
     */
    public function create(User $user, Site $site): bool
    {
        return $user->canManage($site->team);
    }

    /**
     * Determine whether the user can view the command.
     */
    public function view(User $user, Command $command): bool
    {
        return $user->belongsToTeam($command->team);
    }
}
