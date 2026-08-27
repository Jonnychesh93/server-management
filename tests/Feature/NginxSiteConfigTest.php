<?php

use App\Models\Server;
use App\Models\Site;
use App\Services\Provisioning\NginxSiteConfig;

test('the rendered vhost points at the correctly named php-fpm socket', function () {
    $server = Server::factory()->active()->create(['ssh_user' => 'appuser']);
    $site = Site::factory()->for($server)->create([
        'domain' => 'example.com',
        'php_version' => '8.4',
        'document_root' => '/public',
    ]);

    $config = NginxSiteConfig::render($site);

    expect($config)->toContain('fastcgi_pass unix:/run/php/php8.4-fpm.sock;');
    expect($config)->toContain('server_name example.com;');
    expect($config)->toContain('root /home/appuser/example.com/current/public;');
});
