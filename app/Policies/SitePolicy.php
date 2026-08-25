<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;

class SitePolicy
{
    /**
     * Determine whether the user can view the server's sites.
     */
    public function viewAny(User $user): bool
    {
        return $user->currentTeam !== null;
    }

    /**
     * Determine whether the user can view the site.
     */
    public function view(User $user, Site $site): bool
    {
        return $user->belongsToTeam($site->team);
    }

    /**
     * Determine whether the user can add a site to a server.
     */
    public function create(User $user): bool
    {
        return $user->currentTeam && $user->canManage($user->currentTeam);
    }

    /**
     * Determine whether the user can update the site.
     */
    public function update(User $user, Site $site): bool
    {
        return $user->canManage($site->team);
    }

    /**
     * Determine whether the user can delete the site.
     */
    public function delete(User $user, Site $site): bool
    {
        return $user->canManage($site->team);
    }
}
