<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\User;

class ServerPolicy
{
    /**
     * Determine whether the user can view the team's servers.
     */
    public function viewAny(User $user): bool
    {
        return $user->currentTeam !== null;
    }

    /**
     * Determine whether the user can view the server.
     */
    public function view(User $user, Server $server): bool
    {
        return $user->belongsToTeam($server->team);
    }

    /**
     * Determine whether the user can add a server to the team.
     */
    public function create(User $user): bool
    {
        return $user->currentTeam && $user->canManage($user->currentTeam);
    }

    /**
     * Determine whether the user can update the server.
     */
    public function update(User $user, Server $server): bool
    {
        return $user->canManage($server->team);
    }

    /**
     * Determine whether the user can delete the server.
     */
    public function delete(User $user, Server $server): bool
    {
        return $user->canManage($server->team);
    }
}
