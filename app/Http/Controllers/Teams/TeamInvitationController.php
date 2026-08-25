<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\StoreTeamInvitationRequest;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Notifications\TeamInvitationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TeamInvitationController extends Controller
{
    /**
     * Invite a new member to the team by email.
     */
    public function store(StoreTeamInvitationRequest $request, Team $team): RedirectResponse
    {
        $invitation = $team->invitations()->create([
            'email' => $request->validated('email'),
            'role' => $request->validated('role'),
            'invited_by_user_id' => $request->user()->id,
            'token' => TeamInvitation::generateToken(),
            'expires_at' => now()->addDays(7),
        ]);

        $invitation->notify(new TeamInvitationNotification($invitation));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation sent.')]);

        return back();
    }

    /**
     * Revoke a pending invitation.
     */
    public function destroy(Team $team, TeamInvitation $invitation): RedirectResponse
    {
        Gate::authorize('addMember', $team);

        $invitation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation revoked.')]);

        return back();
    }
}
