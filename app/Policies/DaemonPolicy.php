<?php

namespace App\Policies;

use App\Models\Daemon;
use App\Models\Server;
use App\Models\User;

class DaemonPolicy
{
    /**
     * Determine whether the user can add a daemon to the server.
     */
    public function create(User $user, Server $server): bool
    {
        return $user->canManage($server->team);
    }

    /**
     * Determine whether the user can delete the daemon.
     */
    public function delete(User $user, Daemon $daemon): bool
    {
        return $user->canManage($daemon->team);
    }
}
