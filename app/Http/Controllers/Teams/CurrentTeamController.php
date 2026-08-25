<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CurrentTeamController extends Controller
{
    /**
     * Switch the authenticated user's current team.
     */
    public function update(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('view', $team);

        $request->user()->switchTeam($team);

        return back();
    }
}
