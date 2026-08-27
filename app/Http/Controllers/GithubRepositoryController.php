<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Services\GitHub\GitHubAppClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GithubRepositoryController extends Controller
{
    /**
     * List a repository's branches, for the site-creation repo/branch picker.
     */
    public function branches(Request $request, string $owner, string $repo): JsonResponse
    {
        Gate::authorize('create', Site::class);

        $installation = $request->user()->currentTeam->githubInstallation;

        abort_unless($installation, 404);

        $branches = app(GitHubAppClient::class)->repositoryBranches($installation->installation_id, "{$owner}/{$repo}");

        return response()->json(['branches' => $branches]);
    }
}
