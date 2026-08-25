<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Models\TeamInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AcceptTeamInvitationController extends Controller
{
    /**
     * Show the invitation acceptance page.
     */
    public function show(Request $request, TeamInvitation $invitation): Response|RedirectResponse
    {
        if ($invalid = $this->validateInvitation($request, $invitation)) {
            return $invalid;
        }

        return Inertia::render('teams/AcceptInvitation', [
            'invitation' => $invitation->load('team', 'invitedBy'),
        ]);
    }

    /**
     * Accept the invitation and join the team.
     */
    public function store(Request $request, TeamInvitation $invitation): RedirectResponse
    {
        if ($invalid = $this->validateInvitation($request, $invitation)) {
            return $invalid;
        }

        $team = $invitation->team;

        $team->users()->attach($request->user(), ['role' => $invitation->role]);

        $request->user()->switchTeam($team);

        $invitation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('You have joined :team.', ['team' => $team->name])]);

        return to_route('teams.show', $team);
    }

    /**
     * Ensure the invitation is still valid and addressed to the current user.
     */
    private function validateInvitation(Request $request, TeamInvitation $invitation): ?RedirectResponse
    {
        if ($invitation->isExpired()) {
            $invitation->delete();

            Inertia::flash('toast', ['type' => 'error', 'message' => __('This invitation has expired.')]);

            return to_route('dashboard');
        }

        if (strcasecmp($invitation->email, $request->user()->email) !== 0) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('This invitation was sent to a different email address.')]);

            return to_route('dashboard');
        }

        return null;
    }
}
