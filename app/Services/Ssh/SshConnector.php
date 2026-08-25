<?php

namespace App\Services\Ssh;

use App\Models\Server;

/**
 * Thin, container-resolvable seam in front of SshConnection/BootstrapConnection
 * so jobs can depend on this instead of the concrete static factories,
 * letting tests substitute a fake without touching the network.
 */
class SshConnector
{
    public function connect(Server $server): SshConnection
    {
        return SshConnection::for($server);
    }

    public function connectAsRoot(Server $server): SshConnection
    {
        return SshConnection::forRoot($server);
    }

    public function bootstrap(Server $server): BootstrapConnection
    {
        return new BootstrapConnection($server);
    }
}
