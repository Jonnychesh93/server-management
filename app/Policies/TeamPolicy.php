<?php

namespace App\Policies;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    /**
     * Determine whether the user can create teams.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the team.
     */
    public function view(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    /**
     * Determine whether the user can rename the team.
     */
    public function update(User $user, Team $team): bool
    {
        return $user->canManage($team);
    }

    /**
     * Determine whether the user can delete the team.
     */
    public function delete(User $user, Team $team): bool
    {
        return $user->roleOn($team) === TeamRole::Owner;
    }

    /**
     * Determine whether the user can invite new members to the team.
     */
    public function addMember(User $user, Team $team): bool
    {
        return $user->canManage($team);
    }

    /**
     * Determine whether the user can remove the given member from the team.
     */
    public function removeMember(User $user, Team $team, User $member): bool
    {
        return $user->canManage($team) && $team->roleFor($member) !== TeamRole::Owner;
    }

    /**
     * Determine whether the user can change a member's role on the team.
     */
    public function updateMemberRole(User $user, Team $team, User $member): bool
    {
        return $user->canManage($team) && $team->roleFor($member) !== TeamRole::Owner;
    }
}
