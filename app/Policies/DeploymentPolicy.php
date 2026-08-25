<?php

namespace App\Policies;

use App\Models\Deployment;
use App\Models\Site;
use App\Models\User;

class DeploymentPolicy
{
    /**
     * Determine whether the user can view a site's deployment history.
     */
    public function viewAny(User $user): bool
    {
        return $user->currentTeam !== null;
    }

    /**
     * Determine whether the user can view the deployment.
     */
    public function view(User $user, Deployment $deployment): bool
    {
        return $user->belongsToTeam($deployment->team);
    }

    /**
     * Determine whether the user can trigger a deployment for the site.
     *
     * Any team member may trigger a deployment, not just owners/admins —
     * unlike editing the server, site config, or environment.
     */
    public function create(User $user, Site $site): bool
    {
        return $user->belongsToTeam($site->team);
    }
}
