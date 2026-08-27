<?php

namespace App\Policies;

use App\Models\Database;
use App\Models\Server;
use App\Models\User;

class DatabasePolicy
{
    /**
     * Determine whether the user can add a database to the server.
     */
    public function create(User $user, Server $server): bool
    {
        return $user->canManage($server->team);
    }

    /**
     * Determine whether the user can delete the database.
     */
    public function delete(User $user, Database $database): bool
    {
        return $user->canManage($database->team);
    }
}
