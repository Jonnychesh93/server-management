<?php

namespace App\Http\Controllers;

use App\Models\GithubInstallation;
use App\Services\GitHub\GitHubAppClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class GithubInstallationController extends Controller
{
    /**
     * Redirect to GitHub to install (or manage) the App for the current team.
     */
    public function create(Request $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('update', $team);

        $slug = config('services.github.app_slug');
        $state = Crypt::encryptString((string) $team->id);

        return redirect()->away("https://github.com/apps/{$slug}/installations/new?state={$state}");
    }

    /**
     * Handle GitHub's redirect back after the App is installed.
     */
    public function callback(Request $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('update', $team);

        $expectedTeamId = (int) Crypt::decryptString((string) $request->query('state'));

        abort_unless($expectedTeamId === $team->id, 403);

        $installationId = (int) $request->query('installation_id');
        $account = app(GitHubAppClient::class)->installationAccount($installationId);

        GithubInstallation::updateOrCreate(
            ['team_id' => $team->id],
            [
                'installation_id' => $installationId,
                'account_login' => $account['login'],
                'account_type' => $account['type'],
            ],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('GitHub connected.')]);

        return to_route('teams.show', $team);
    }

    /**
     * Disconnect the team's GitHub App installation.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('update', $team);

        $team->githubInstallation?->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('GitHub disconnected.')]);

        return to_route('teams.show', $team);
    }
}
