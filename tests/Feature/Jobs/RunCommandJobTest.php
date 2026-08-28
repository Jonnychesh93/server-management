<?php

use App\Enums\CommandStatus;
use App\Jobs\RunCommandJob;
use App\Models\Command;
use App\Models\Server;
use App\Models\Site;
use App\Services\Ssh\SshConnection;
use App\Services\Ssh\SshConnector;
use App\Services\Ssh\SshResult;

test('a successful command records its output and exit code', function () {
    $server = Server::factory()->active()->create();
    $site = Site::factory()->for($server->team)->for($server)->active()->create();
    $command = Command::factory()->for($server->team)->for($site)->create(['command' => 'php artisan about']);

    $connection = Mockery::mock(SshConnection::class);
    $connection->shouldReceive('run')->andReturnUsing(function (string $script, ?callable $onOutput = null) {
        $onOutput && $onOutput("ok\n");

        return new SshResult(0, "ok\n");
    });

    $connector = Mockery::mock(SshConnector::class);
    $connector->shouldReceive('connect')->andReturn($connection);

    app()->instance(SshConnector::class, $connector);

    app()->call([new RunCommandJob($command), 'handle']);

    $command->refresh();

    expect($command->status)->toBe(CommandStatus::Success);
    expect($command->exit_code)->toBe(0);
    expect($command->output)->toContain("ok\n");
    expect($command->finished_at)->not->toBeNull();
});

test('a failing command is recorded as failed with its exit code', function () {
    $server = Server::factory()->active()->create();
    $site = Site::factory()->for($server->team)->for($server)->active()->create();
    $command = Command::factory()->for($server->team)->for($site)->create();

    $connection = Mockery::mock(SshConnection::class);
    $connection->shouldReceive('run')->andReturn(new SshResult(1, 'boom'));

    $connector = Mockery::mock(SshConnector::class);
    $connector->shouldReceive('connect')->andReturn($connection);

    app()->instance(SshConnector::class, $connector);

    app()->call([new RunCommandJob($command), 'handle']);

    $command->refresh();

    expect($command->status)->toBe(CommandStatus::Failed);
    expect($command->exit_code)->toBe(1);
});

test('the command runs from the site\'s current release with the php version shimmed', function () {
    $server = Server::factory()->active()->create();
    $site = Site::factory()->for($server->team)->for($server)->active()->create(['php_version' => '8.2']);
    $command = Command::factory()->for($server->team)->for($site)->create(['command' => 'php artisan queue:restart']);

    $ranScript = null;
    $connection = Mockery::mock(SshConnection::class);
    $connection->shouldReceive('run')->andReturnUsing(function (string $script) use (&$ranScript) {
        $ranScript = $script;

        return new SshResult(0, 'ok');
    });

    $connector = Mockery::mock(SshConnector::class);
    $connector->shouldReceive('connect')->andReturn($connection);

    app()->instance(SshConnector::class, $connector);

    app()->call([new RunCommandJob($command), 'handle']);

    expect($ranScript)->toContain($site->remotePath().'/current');
    expect($ranScript)->toContain('ln -sf /usr/bin/php8.2 ');
    expect($ranScript)->toContain('.bin:/usr/local/bin:$PATH');
    expect($ranScript)->toContain('php artisan queue:restart');
});

test('an exception while running the command marks it failed and preserves partial output', function () {
    $server = Server::factory()->active()->create();
    $site = Site::factory()->for($server->team)->for($server)->active()->create();
    $command = Command::factory()->for($server->team)->for($site)->create();

    $connection = Mockery::mock(SshConnection::class);
    $connection->shouldReceive('run')->andThrow(new RuntimeException('connection lost'));

    $connector = Mockery::mock(SshConnector::class);
    $connector->shouldReceive('connect')->andReturn($connection);

    app()->instance(SshConnector::class, $connector);

    app()->call([new RunCommandJob($command), 'handle']);

    $command->refresh();

    expect($command->status)->toBe(CommandStatus::Failed);
    expect($command->output)->toContain('connection lost');
});
