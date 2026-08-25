<?php

use App\Enums\DeploymentStatus;
use App\Jobs\RunDeploymentJob;
use App\Models\Deployment;
use App\Models\GitConnection;
use App\Models\Server;
use App\Models\Site;
use App\Models\SshKey;
use App\Services\Ssh\SshConnection;
use App\Services\Ssh\SshConnector;
use App\Services\Ssh\SshResult;

test('a successful deployment clones, deploys, activates, and records the commit', function () {
    $server = Server::factory()->active()->create();
    $site = Site::factory()->for($server->team)->for($server)->active()->create();
    $key = SshKey::factory()->for($server->team)->for($site)->create();
    GitConnection::factory()->for($site)->create(['deploy_key_id' => $key->id]);
    $deployment = Deployment::factory()->for($server->team)->for($site)->create();

    $connection = Mockery::mock(SshConnection::class);
    $connection->shouldReceive('writeFile')->andReturnNull();
    $connection->shouldReceive('run')->andReturnUsing(function (string $script, ?callable $onOutput = null) {
        $onOutput && $onOutput("ok\n");

        if (str_contains($script, 'git clone')) {
            return new SshResult(0, "DEPLOY_SHA=abc123\nDEPLOY_MSG=Fix the thing\n");
        }

        return new SshResult(0, 'ok');
    });

    $connector = Mockery::mock(SshConnector::class);
    $connector->shouldReceive('connect')->andReturn($connection);

    app()->instance(SshConnector::class, $connector);

    app()->call([new RunDeploymentJob($deployment), 'handle']);

    $deployment->refresh();

    expect($deployment->status)->toBe(DeploymentStatus::Success);
    expect($deployment->commit_sha)->toBe('abc123');
    expect($deployment->commit_message)->toBe('Fix the thing');
    expect($site->refresh()->last_deployment_id)->toBe($deployment->id);
});

test('a deployment with no git connection fails immediately', function () {
    $server = Server::factory()->active()->create();
    $site = Site::factory()->for($server->team)->for($server)->active()->create();
    $deployment = Deployment::factory()->for($server->team)->for($site)->create();

    app()->call([new RunDeploymentJob($deployment), 'handle']);

    $deployment->refresh();

    expect($deployment->status)->toBe(DeploymentStatus::Failed);
    expect($deployment->failed_step)->toBe('resolve_repository');
});

test('a failing deploy script marks the deployment failed at that step', function () {
    $server = Server::factory()->active()->create();
    $site = Site::factory()->for($server->team)->for($server)->active()->create();
    GitConnection::factory()->for($site)->create();
    $deployment = Deployment::factory()->for($server->team)->for($site)->create();

    $connection = Mockery::mock(SshConnection::class);
    $connection->shouldReceive('writeFile')->andReturnNull();

    $calls = 0;
    $connection->shouldReceive('run')->andReturnUsing(function () use (&$calls) {
        $calls++;

        // Call order: clone, then deploy script.
        if ($calls === 2) {
            return new SshResult(1, 'composer: command not found');
        }

        return new SshResult(0, "DEPLOY_SHA=abc123\nDEPLOY_MSG=msg\n");
    });

    $connector = Mockery::mock(SshConnector::class);
    $connector->shouldReceive('connect')->andReturn($connection);

    app()->instance(SshConnector::class, $connector);

    app()->call([new RunDeploymentJob($deployment), 'handle']);

    $deployment->refresh();

    expect($deployment->status)->toBe(DeploymentStatus::Failed);
    expect($deployment->failed_step)->toBe('run_deploy_script');
});
