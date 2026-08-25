<?php

use App\Enums\CronStatus;
use App\Enums\DaemonStatus;
use App\Jobs\SyncCronJob;
use App\Jobs\SyncDaemonJob;
use App\Models\Cron;
use App\Models\Daemon;
use App\Models\Server;
use App\Services\Ssh\SshConnection;
use App\Services\Ssh\SshConnector;
use App\Services\Ssh\SshResult;

test('syncing a daemon writes its supervisor config and marks it active', function () {
    $server = Server::factory()->active()->create();
    $daemon = Daemon::factory()->for($server->team)->for($server)->create();

    $connection = Mockery::mock(SshConnection::class);
    $connection->shouldReceive('writeFile')
        ->once()
        ->with("/etc/supervisor/conf.d/{$daemon->slug()}.conf", Mockery::type('string'));
    $connection->shouldReceive('run')->andReturn(new SshResult(0, 'ok'));

    $connector = Mockery::mock(SshConnector::class);
    $connector->shouldReceive('connectAsRoot')->andReturn($connection);

    app()->instance(SshConnector::class, $connector);

    app()->call([new SyncDaemonJob($daemon), 'handle']);

    expect($daemon->refresh()->status)->toBe(DaemonStatus::Active);
});

test('a failing supervisorctl update marks the daemon failed', function () {
    $server = Server::factory()->active()->create();
    $daemon = Daemon::factory()->for($server->team)->for($server)->create();

    $connection = Mockery::mock(SshConnection::class);
    $connection->shouldReceive('writeFile')->andReturnNull();
    $connection->shouldReceive('run')->andReturn(new SshResult(1, 'error'));

    $connector = Mockery::mock(SshConnector::class);
    $connector->shouldReceive('connectAsRoot')->andReturn($connection);

    app()->instance(SshConnector::class, $connector);

    app()->call([new SyncDaemonJob($daemon), 'handle']);

    expect($daemon->refresh()->status)->toBe(DaemonStatus::Failed);
});

test('syncing a cron writes its cron.d file and marks it active', function () {
    $server = Server::factory()->active()->create();
    $cron = Cron::factory()->for($server->team)->for($server)->create();

    $connection = Mockery::mock(SshConnection::class);
    $connection->shouldReceive('writeFile')
        ->once()
        ->with("/etc/cron.d/{$cron->filename()}", "{$cron->schedule} {$cron->user} {$cron->command}\n");

    $connector = Mockery::mock(SshConnector::class);
    $connector->shouldReceive('connectAsRoot')->andReturn($connection);

    app()->instance(SshConnector::class, $connector);

    app()->call([new SyncCronJob($cron), 'handle']);

    expect($cron->refresh()->status)->toBe(CronStatus::Active);
});
