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
use phpseclib3\Net\SFTP;

test('a successful deployment clones, deploys, activates, and records the commit', function () {
    $server = Server::factory()->active()->create();
    $site = Site::factory()->for($server->team)->for($server)->active()->create();
    $key = SshKey::factory()->for($server->team)->for($site)->create();
    GitConnection::factory()->for($site)->create(['deploy_key_id' => $key->id]);
    $deployment = Deployment::factory()->for($server->team)->for($site)->create();

    $sftp = Mockery::mock(SFTP::class);
    $sftp->shouldReceive('get')->andReturn(false);

    $connection = Mockery::mock(SshConnection::class);
    $connection->shouldReceive('writeFile')->andReturnNull();
    $connection->shouldReceive('sftp')->andReturn($sftp);
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

test('the first deployment seeds .env from the repository\'s own .env.example', function () {
    $server = Server::factory()->active()->create();
    $site = Site::factory()->for($server->team)->for($server)->active()->create();
    GitConnection::factory()->for($site)->create();
    $deployment = Deployment::factory()->for($server->team)->for($site)->create();

    $sftp = Mockery::mock(SFTP::class);
    $sftp->shouldReceive('get')->andReturn("APP_NAME=FromTheRepo\n");

    $writtenEnv = null;
    $connection = Mockery::mock(SshConnection::class);
    $connection->shouldReceive('sftp')->andReturn($sftp);
    $connection->shouldReceive('writeFile')->andReturnUsing(function (string $path, string $contents) use (&$writtenEnv) {
        if (str_ends_with($path, '/shared/.env')) {
            $writtenEnv = $contents;
        }
    });
    $connection->shouldReceive('run')->andReturn(new SshResult(0, "DEPLOY_SHA=abc123\nDEPLOY_MSG=msg\n"));

    $connector = Mockery::mock(SshConnector::class);
    $connector->shouldReceive('connect')->andReturn($connection);

    app()->instance(SshConnector::class, $connector);

    app()->call([new RunDeploymentJob($deployment), 'handle']);

    expect($site->refresh()->env_encrypted)->toBe("APP_NAME=FromTheRepo\n");
    expect($writtenEnv)->toBe("APP_NAME=FromTheRepo\n");
});

test('the first deployment falls back to the default env template when the repository has no .env.example', function () {
    $server = Server::factory()->active()->create();
    $site = Site::factory()->for($server->team)->for($server)->active()->create();
    GitConnection::factory()->for($site)->create();
    $deployment = Deployment::factory()->for($server->team)->for($site)->create();

    $sftp = Mockery::mock(SFTP::class);
    $sftp->shouldReceive('get')->andReturn(false);

    $connection = Mockery::mock(SshConnection::class);
    $connection->shouldReceive('sftp')->andReturn($sftp);
    $connection->shouldReceive('writeFile')->andReturnNull();
    $connection->shouldReceive('run')->andReturn(new SshResult(0, "DEPLOY_SHA=abc123\nDEPLOY_MSG=msg\n"));

    $connector = Mockery::mock(SshConnector::class);
    $connector->shouldReceive('connect')->andReturn($connection);

    app()->instance(SshConnector::class, $connector);

    app()->call([new RunDeploymentJob($deployment), 'handle']);

    expect($site->refresh()->env_encrypted)->toBe(Site::DEFAULT_ENV_TEMPLATE);
});

test('a second deployment does not overwrite an already-customized .env', function () {
    $server = Server::factory()->active()->create();
    $site = Site::factory()->for($server->team)->for($server)->active()->create(['env_encrypted' => 'APP_NAME=AlreadyCustomized']);
    GitConnection::factory()->for($site)->create();
    $deployment = Deployment::factory()->for($server->team)->for($site)->create();

    $connection = Mockery::mock(SshConnection::class);
    $connection->shouldReceive('writeFile')->andReturnNull();
    $connection->shouldNotReceive('sftp');
    $connection->shouldReceive('run')->andReturn(new SshResult(0, "DEPLOY_SHA=abc123\nDEPLOY_MSG=msg\n"));

    $connector = Mockery::mock(SshConnector::class);
    $connector->shouldReceive('connect')->andReturn($connection);

    app()->instance(SshConnector::class, $connector);

    app()->call([new RunDeploymentJob($deployment), 'handle']);

    expect($site->refresh()->env_encrypted)->toBe('APP_NAME=AlreadyCustomized');
});

test('the deploy script shims php to the site\'s own configured version', function () {
    $server = Server::factory()->active()->create();
    $site = Site::factory()->for($server->team)->for($server)->active()->create(['php_version' => '8.1']);
    GitConnection::factory()->for($site)->create();
    $deployment = Deployment::factory()->for($server->team)->for($site)->create();

    $sftp = Mockery::mock(SFTP::class);
    $sftp->shouldReceive('get')->andReturn(false);

    $deployScript = null;
    $connection = Mockery::mock(SshConnection::class);
    $connection->shouldReceive('sftp')->andReturn($sftp);
    $connection->shouldReceive('writeFile')->andReturnNull();
    $connection->shouldReceive('run')->andReturnUsing(function (string $script) use (&$deployScript) {
        if (str_contains($script, 'composer install')) {
            $deployScript = $script;
        }

        return new SshResult(0, "DEPLOY_SHA=abc123\nDEPLOY_MSG=msg\n");
    });

    $connector = Mockery::mock(SshConnector::class);
    $connector->shouldReceive('connect')->andReturn($connection);

    app()->instance(SshConnector::class, $connector);

    app()->call([new RunDeploymentJob($deployment), 'handle']);

    expect($deployScript)->toContain('ln -sf /usr/bin/php8.1 ');
    expect($deployScript)->toContain('.bin:/usr/local/bin:$PATH');
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

    $sftp = Mockery::mock(SFTP::class);
    $sftp->shouldReceive('get')->andReturn(false);

    $connection = Mockery::mock(SshConnection::class);
    $connection->shouldReceive('writeFile')->andReturnNull();
    $connection->shouldReceive('sftp')->andReturn($sftp);

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

test('the deploy script actually halts on a failing custom command instead of masking it', function () {
    $server = Server::factory()->active()->create();
    $releasePath = sys_get_temp_dir().'/anchor-deploy-script-test-'.uniqid();
    $site = Site::factory()->for($server->team)->for($server)->active()->create([
        'deploy_script' => "false\necho 'this must not print'",
    ]);

    $method = (new ReflectionClass(RunDeploymentJob::class))->getMethod('deployScript');
    $method->setAccessible(true);
    $job = (new ReflectionClass(RunDeploymentJob::class))->newInstanceWithoutConstructor();
    $script = $method->invoke($job, $releasePath, $site);

    exec('bash -c '.escapeshellarg($script).' 2>&1', $output, $exitCode);

    expect($exitCode)->not->toBe(0);
    expect(implode("\n", $output))->not->toContain('this must not print');

    exec('rm -rf '.escapeshellarg($releasePath));
});
