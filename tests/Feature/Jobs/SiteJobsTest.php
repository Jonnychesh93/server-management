<?php

use App\Enums\SslStatus;
use App\Jobs\IssueSslCertificateJob;
use App\Jobs\SyncSiteEnvironmentJob;
use App\Models\Server;
use App\Models\Site;
use App\Services\Ssh\SshConnection;
use App\Services\Ssh\SshConnector;
use App\Services\Ssh\SshResult;

test('syncing environment writes the .env file to the site directory', function () {
    $server = Server::factory()->active()->create();
    $site = Site::factory()->for($server->team)->for($server)->active()->create([
        'env_encrypted' => 'APP_NAME=Example',
    ]);

    $connection = Mockery::mock(SshConnection::class);
    $connection->shouldReceive('writeFile')
        ->once()
        ->with($site->remotePath().'/shared/.env', 'APP_NAME=Example', 0600);

    $connector = Mockery::mock(SshConnector::class);
    $connector->shouldReceive('connect')->andReturn($connection);

    app()->instance(SshConnector::class, $connector);

    app()->call([new SyncSiteEnvironmentJob($site), 'handle']);
});

test('issuing an ssl certificate marks the site active with an expiry', function () {
    $server = Server::factory()->active()->create();
    $site = Site::factory()->for($server->team)->for($server)->active()->create();

    $connection = Mockery::mock(SshConnection::class);
    $connection->shouldReceive('run')->andReturn(new SshResult(0, 'ok'));

    $connector = Mockery::mock(SshConnector::class);
    $connector->shouldReceive('connectAsRoot')->andReturn($connection);

    app()->instance(SshConnector::class, $connector);

    app()->call([new IssueSslCertificateJob($site), 'handle']);

    $site->refresh();

    expect($site->ssl_status)->toBe(SslStatus::Active);
    expect($site->ssl_certificate_expires_at)->not->toBeNull();
});

test('a failing certbot run marks ssl as failed', function () {
    $server = Server::factory()->active()->create();
    $site = Site::factory()->for($server->team)->for($server)->active()->create();

    $connection = Mockery::mock(SshConnection::class);
    $connection->shouldReceive('run')->andReturn(new SshResult(1, 'certbot: error'));

    $connector = Mockery::mock(SshConnector::class);
    $connector->shouldReceive('connectAsRoot')->andReturn($connection);

    app()->instance(SshConnector::class, $connector);

    app()->call([new IssueSslCertificateJob($site), 'handle']);

    expect($site->refresh()->ssl_status)->toBe(SslStatus::Failed);
});
