<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\UpdateTeamMemberRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TeamMemberController extends Controller
{
    /**
     * Change a team member's role.
     */
    public function update(UpdateTeamMemberRequest $request, Team $team, User $member): RedirectResponse
    {
        $team->users()->updateExistingPivot($member->id, ['role' => $request->validated('role')]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member role updated.')]);

        return back();
    }

    /**
     * Remove a member from the team.
     */
    public function destroy(Team $team, User $member): RedirectResponse
    {
        Gate::authorize('removeMember', [$team, $member]);

        $team->users()->detach($member);

        if ($member->current_team_id === $team->id) {
            $fallback = $member->teams()->whereKeyNot($team->id)->oldest('team_user.id')->first();
            $member->forceFill(['current_team_id' => $fallback?->id])->save();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member removed from team.')]);

        return back();
    }
}
