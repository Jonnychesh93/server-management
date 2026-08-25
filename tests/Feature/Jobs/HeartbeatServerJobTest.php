<?php

use App\Enums\ServerConnectionStatus;
use App\Jobs\HeartbeatServerJob;
use App\Models\Server;
use App\Models\ServerMetric;
use App\Services\Ssh\SshConnection;
use App\Services\Ssh\SshConnector;
use App\Services\Ssh\SshResult;

test('a successful heartbeat records metrics and marks the server online', function () {
    $server = Server::factory()->active()->create();

    $connection = Mockery::mock(SshConnection::class);
    $connection->shouldReceive('run')->andReturn(
        new SshResult(0, 'HEARTBEAT cpu=42 memory=55 disk=30 load1=0.75'),
    );

    $connector = Mockery::mock(SshConnector::class);
    $connector->shouldReceive('connect')->andReturn($connection);

    app()->instance(SshConnector::class, $connector);

    app()->call([new HeartbeatServerJob($server), 'handle']);

    $server->refresh();

    expect($server->connection_status)->toBe(ServerConnectionStatus::Online);
    expect($server->cpu_usage)->toBe(42);
    expect($server->memory_usage)->toBe(55);
    expect($server->disk_usage)->toBe(30);
    expect($server->last_heartbeat_at)->not->toBeNull();

    expect(ServerMetric::where('server_id', $server->id)->count())->toBe(1);
});

test('an unreachable server is marked offline', function () {
    $server = Server::factory()->active()->create();

    $connection = Mockery::mock(SshConnection::class);
    $connection->shouldReceive('run')->andThrow(new RuntimeException('Connection refused.'));

    $connector = Mockery::mock(SshConnector::class);
    $connector->shouldReceive('connect')->andReturn($connection);

    app()->instance(SshConnector::class, $connector);

    app()->call([new HeartbeatServerJob($server), 'handle']);

    expect($server->refresh()->connection_status)->toBe(ServerConnectionStatus::Offline);
});

test('heartbeats are skipped for servers that are not active yet', function () {
    $server = Server::factory()->create();

    $connector = Mockery::mock(SshConnector::class);
    $connector->shouldNotReceive('connect');

    app()->instance(SshConnector::class, $connector);

    app()->call([new HeartbeatServerJob($server), 'handle']);
});
