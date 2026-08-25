<?php

use App\Jobs\RunDeploymentJob;
use App\Models\GitConnection;
use App\Models\Site;
use Illuminate\Support\Facades\Bus;

test('a correctly signed webhook triggers a deployment', function () {
    Bus::fake();

    $site = Site::factory()->active()->create();
    $connection = GitConnection::factory()->for($site)->create(['webhook_secret' => 'top-secret']);

    $payload = '{"ref":"refs/heads/main"}';
    $signature = 'sha256='.hash_hmac('sha256', $payload, 'top-secret');

    $this->call(
        'POST',
        route('webhooks.sites.deploy', $site),
        [],
        [],
        [],
        ['HTTP_X_HUB_SIGNATURE_256' => $signature],
        $payload,
    )->assertNoContent();

    Bus::assertDispatched(RunDeploymentJob::class);
});

test('a webhook with an invalid signature is rejected', function () {
    Bus::fake();

    $site = Site::factory()->active()->create();
    GitConnection::factory()->for($site)->create(['webhook_secret' => 'top-secret']);

    $this->withHeaders(['X-Hub-Signature-256' => 'sha256=wrong'])
        ->post(route('webhooks.sites.deploy', $site), ['ref' => 'refs/heads/main'])
        ->assertUnauthorized();

    Bus::assertNotDispatched(RunDeploymentJob::class);
});

test('a webhook for a site with no git connection 404s', function () {
    $site = Site::factory()->active()->create();

    $this->post(route('webhooks.sites.deploy', $site))->assertNotFound();
});
