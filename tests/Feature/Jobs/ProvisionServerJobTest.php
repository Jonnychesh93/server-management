<?php

use App\Enums\ServerConnectionStatus;
use App\Enums\ServerProvisioningStatus;
use App\Jobs\ProvisionServerJob;
use App\Models\Server;
use App\Services\Provisioning\Steps\InstallPhp;
use App\Services\Ssh\BootstrapConnection;
use App\Services\Ssh\SshConnection;
use App\Services\Ssh\SshConnector;
use App\Services\Ssh\SshResult;

test('provisioning a server through every step marks it active', function () {
    $server = Server::factory()->create();

    $connector = fakeSshConnector(function (string $script, ?callable $onOutput = null) {
        $onOutput && $onOutput("ok\n");

        return new SshResult(0, "ok\n");
    });

    app()->instance(SshConnector::class, $connector);

    app()->call([new ProvisionServerJob($server), 'handle']);

    $server->refresh();

    expect($server->provisioning_status)->toBe(ServerProvisioningStatus::Active);
    expect($server->connection_status)->toBe(ServerConnectionStatus::Online);
    expect($server->ssh_user)->toBe('appuser');
    expect($server->installed_php_versions)->toBe([InstallPhp::DEFAULT_VERSION]);
    expect($server->provisioning_output)->toContain('install_certbot');
});

test('a failing step marks provisioning as failed and records where', function () {
    $server = Server::factory()->create();

    $calls = 0;
    $connector = fakeSshConnector(function (string $script, ?callable $onOutput = null) use (&$calls) {
        $calls++;
        $onOutput && $onOutput("output {$calls}\n");

        // Step order: configure_swap, update_packages, create_deploy_user, install_firewall, ...
        if ($calls === 4) {
            return new SshResult(1, 'ufw: command not found');
        }

        return new SshResult(0, 'ok');
    });

    app()->instance(SshConnector::class, $connector);

    app()->call([new ProvisionServerJob($server), 'handle']);

    $server->refresh();

    expect($server->provisioning_status)->toBe(ServerProvisioningStatus::Failed);
    expect($server->provisioning_failed_step)->toBe('install_firewall');
    expect($server->provisioning_output)->toContain('Failed at [install_firewall]');
});

test('retrying after a control plane key already exists connects as root directly, skipping the original credential', function () {
    $server = Server::factory()->create(['ssh_private_key' => 'already-installed-key']);

    $connection = Mockery::mock(SshConnection::class);
    $connection->shouldReceive('run')->andReturnUsing(function () {
        return new SshResult(0, "ok\n");
    });

    $connector = Mockery::mock(SshConnector::class);
    $connector->shouldReceive('connectAsRoot')->once()->andReturn($connection);
    $connector->shouldNotReceive('bootstrap');

    app()->instance(SshConnector::class, $connector);

    app()->call([new ProvisionServerJob($server), 'handle']);

    $server->refresh();

    expect($server->provisioning_status)->toBe(ServerProvisioningStatus::Active);
});

test('a failed bootstrap connection marks provisioning as failed at connecting', function () {
    $server = Server::factory()->create();

    $bootstrap = Mockery::mock(BootstrapConnection::class);
    $bootstrap->shouldReceive('installControlPlaneKey')->andThrow(new RuntimeException('Unable to authenticate.'));

    $connector = Mockery::mock(SshConnector::class);
    $connector->shouldReceive('bootstrap')->andReturn($bootstrap);

    app()->instance(SshConnector::class, $connector);

    app()->call([new ProvisionServerJob($server), 'handle']);

    $server->refresh();

    expect($server->provisioning_status)->toBe(ServerProvisioningStatus::Failed);
    expect($server->provisioning_failed_step)->toBe('connecting');
});
