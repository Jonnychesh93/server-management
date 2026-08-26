<?php

use App\Enums\GitProvider;
use App\Jobs\RunDeploymentJob;
use App\Models\GitConnection;
use App\Models\Site;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

function postGithubWebhook(TestCase $testCase, string $payload, string $secret, string $event = 'push')
{
    $signature = 'sha256='.hash_hmac('sha256', $payload, $secret);

    return $testCase->call(
        'POST',
        route('webhooks.github'),
        [],
        [],
        [],
        [
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
            'HTTP_X_GITHUB_EVENT' => $event,
            'CONTENT_TYPE' => 'application/json',
        ],
        $payload,
    );
}

test('a valid push event deploys every matching site', function () {
    Bus::fake();
    config(['services.github.webhook_secret' => 'app-secret']);

    $site = Site::factory()->active()->create();
    GitConnection::factory()->for($site)->create([
        'provider' => GitProvider::GitHubApp,
        'repository' => 'acme/example',
        'branch' => 'main',
        'installation_id' => '999',
    ]);

    $payload = json_encode([
        'ref' => 'refs/heads/main',
        'installation' => ['id' => 999],
        'repository' => ['full_name' => 'acme/example'],
    ]);

    postGithubWebhook($this, $payload, 'app-secret')->assertNoContent();

    Bus::assertDispatched(RunDeploymentJob::class, fn ($job) => $job->deployment->site_id === $site->id);
});

test('a push to a different branch does not trigger a deployment', function () {
    Bus::fake();
    config(['services.github.webhook_secret' => 'app-secret']);

    $site = Site::factory()->active()->create();
    GitConnection::factory()->for($site)->create([
        'provider' => GitProvider::GitHubApp,
        'repository' => 'acme/example',
        'branch' => 'main',
        'installation_id' => '999',
    ]);

    $payload = json_encode([
        'ref' => 'refs/heads/some-feature',
        'installation' => ['id' => 999],
        'repository' => ['full_name' => 'acme/example'],
    ]);

    postGithubWebhook($this, $payload, 'app-secret')->assertNoContent();

    Bus::assertNotDispatched(RunDeploymentJob::class);
});

test('an invalid signature is rejected', function () {
    Bus::fake();
    config(['services.github.webhook_secret' => 'app-secret']);

    postGithubWebhook($this, '{}', 'wrong-secret')->assertUnauthorized();

    Bus::assertNotDispatched(RunDeploymentJob::class);
});
