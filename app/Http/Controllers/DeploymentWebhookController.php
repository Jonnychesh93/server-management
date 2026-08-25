<?php

namespace App\Http\Controllers;

use App\Enums\DeploymentTriggerType;
use App\Jobs\RunDeploymentJob;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DeploymentWebhookController extends Controller
{
    /**
     * Trigger a deployment from an external push notification.
     *
     * Verifies the payload the same way GitHub signs its webhooks
     * (HMAC-SHA256 of the raw body, hex-encoded, prefixed "sha256="), so a
     * site's webhook URL and secret can be pasted directly into a GitHub
     * repository's webhook settings today.
     */
    public function store(Request $request, Site $site): Response
    {
        $connection = $site->gitConnection;

        if (! $connection || ! $connection->webhook_secret) {
            abort(404);
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $connection->webhook_secret);

        if (! hash_equals($expected, (string) $request->header('X-Hub-Signature-256'))) {
            abort(401, 'Invalid webhook signature.');
        }

        $deployment = $site->deployments()->create([
            'team_id' => $site->team_id,
            'triggered_by_type' => DeploymentTriggerType::Webhook,
        ]);

        RunDeploymentJob::dispatch($deployment);

        return response()->noContent();
    }
}
