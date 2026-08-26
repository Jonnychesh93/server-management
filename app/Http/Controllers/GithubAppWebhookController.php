<?php

namespace App\Http\Controllers;

use App\Enums\DeploymentTriggerType;
use App\Enums\GitProvider;
use App\Jobs\RunDeploymentJob;
use App\Models\GitConnection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GithubAppWebhookController extends Controller
{
    /**
     * Handle a webhook delivery from the GitHub App installation.
     *
     * One endpoint receives events for every team's installation, since a
     * GitHub App has a single, App-wide webhook URL and secret rather than
     * one per repository.
     */
    public function store(Request $request): Response
    {
        $secret = config('services.github.webhook_secret');
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), (string) $secret);

        if (! $secret || ! hash_equals($expected, (string) $request->header('X-Hub-Signature-256'))) {
            abort(401, 'Invalid webhook signature.');
        }

        if ($request->header('X-GitHub-Event') !== 'push') {
            return response()->noContent();
        }

        $installationId = $request->input('installation.id');
        $repository = $request->input('repository.full_name');
        $branch = str($request->input('ref', ''))->after('refs/heads/')->toString();

        if (! $installationId || ! $repository || ! $branch) {
            return response()->noContent();
        }

        GitConnection::query()
            ->where('provider', GitProvider::GitHubApp)
            ->where('installation_id', (string) $installationId)
            ->where('repository', $repository)
            ->where('branch', $branch)
            ->get()
            ->each(function (GitConnection $connection) {
                $site = $connection->site;

                $deployment = $site->deployments()->create([
                    'team_id' => $site->team_id,
                    'triggered_by_type' => DeploymentTriggerType::Webhook,
                ]);

                RunDeploymentJob::dispatch($deployment);
            });

        return response()->noContent();
    }
}
