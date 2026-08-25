<?php

use App\Enums\SiteStatus;
use App\Jobs\ProvisionSiteJob;
use App\Models\Server;
use App\Models\Site;
use App\Services\Ssh\SshConnector;
use App\Services\Ssh\SshResult;

test('provisioning a site through every step marks it active', function () {
    $server = Server::factory()->active()->create(['installed_php_versions' => ['8.3']]);
    $site = Site::factory()->for($server->team)->for($server)->create(['php_version' => '8.3']);

    $connector = fakeSiteSshConnector(function (string $script, ?callable $onOutput = null) {
        $onOutput && $onOutput("ok\n");

        return new SshResult(0, "ok\n");
    });

    app()->instance(SshConnector::class, $connector);

    app()->call([new ProvisionSiteJob($site), 'handle']);

    $site->refresh();

    expect($site->status)->toBe(SiteStatus::Active);
    expect($site->provisioning_output)->toContain('configuring nginx');
});

test('installing a missing php version records it on the server', function () {
    $server = Server::factory()->active()->create(['installed_php_versions' => ['8.3']]);
    $site = Site::factory()->for($server->team)->for($server)->create(['php_version' => '8.1']);

    $connector = fakeSiteSshConnector(fn () => new SshResult(0, 'ok'));

    app()->instance(SshConnector::class, $connector);

    app()->call([new ProvisionSiteJob($site), 'handle']);

    expect($server->refresh()->installed_php_versions)->toBe(['8.3', '8.1']);
});

test('a failing nginx configuration marks the site as failed', function () {
    $server = Server::factory()->active()->create(['installed_php_versions' => ['8.3']]);
    $site = Site::factory()->for($server->team)->for($server)->create(['php_version' => '8.3']);

    $calls = 0;
    $connector = fakeSiteSshConnector(function () use (&$calls) {
        $calls++;

        // Call order: create_directories, then configure_nginx.
        if ($calls === 2) {
            return new SshResult(1, 'nginx: configuration file test failed');
        }

        return new SshResult(0, 'ok');
    });

    app()->instance(SshConnector::class, $connector);

    app()->call([new ProvisionSiteJob($site), 'handle']);

    $site->refresh();

    expect($site->status)->toBe(SiteStatus::Failed);
    expect($site->provisioning_failed_step)->toBe('configure_nginx');
});
