<?php

namespace App\Http\Controllers\Teams;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\StoreTeamRequest;
use App\Http\Requests\Teams\UpdateTeamRequest;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    /**
     * Show the form for creating an additional team.
     */
    public function create(): Response
    {
        Gate::authorize('create', Team::class);

        return Inertia::render('teams/Create');
    }

    /**
     * Create a new team owned by the current user and switch to it.
     */
    public function store(StoreTeamRequest $request): RedirectResponse
    {
        $team = Team::create($request->validated());

        $team->users()->attach($request->user(), ['role' => TeamRole::Owner]);

        $request->user()->switchTeam($team);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team created.')]);

        return to_route('teams.show', $team);
    }

    /**
     * Show the team management page (members, invitations, settings).
     */
    public function show(Team $team): Response
    {
        Gate::authorize('view', $team);

        $team->load(['users' => fn ($query) => $query->orderBy('team_user.created_at'), 'invitations']);

        return Inertia::render('teams/Show', [
            'team' => $team,
            'canManage' => request()->user()->canManage($team),
        ]);
    }

    /**
     * Rename the team.
     */
    public function update(UpdateTeamRequest $request, Team $team): RedirectResponse
    {
        $team->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team updated.')]);

        return to_route('teams.show', $team);
    }

    /**
     * Delete the team, provided it is not the owner's only team.
     */
    public function destroy(Team $team): RedirectResponse
    {
        Gate::authorize('delete', $team);

        $owner = request()->user();

        if ($owner->teams()->count() <= 1) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('You cannot delete your only team.')]);

            return back();
        }

        $team->users->each(function ($member) use ($team) {
            if ($member->current_team_id === $team->id) {
                $fallback = $member->teams()->whereKeyNot($team->id)->oldest('team_user.id')->first();
                $member->forceFill(['current_team_id' => $fallback?->id])->save();
            }
        });

        $team->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team deleted.')]);

        return to_route('dashboard');
    }
}
