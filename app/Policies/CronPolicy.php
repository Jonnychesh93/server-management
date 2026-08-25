<?php

namespace App\Policies;

use App\Models\Cron;
use App\Models\Server;
use App\Models\User;

class CronPolicy
{
    /**
     * Determine whether the user can add a cron job to the server.
     */
    public function create(User $user, Server $server): bool
    {
        return $user->canManage($server->team);
    }

    /**
     * Determine whether the user can delete the cron job.
     */
    public function delete(User $user, Cron $cron): bool
    {
        return $user->canManage($cron->team);
    }
}
