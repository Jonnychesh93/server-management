<?php

namespace App\Services\Ssh;

use App\Models\Server;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP;
use RuntimeException;

/**
 * Authenticated connection to a server using the control plane's own
 * keypair, used by every job except first-time bootstrapping.
 */
class SshConnection
{
    private SFTP $client;

    private function __construct(Server $server, string $user)
    {
        if (! $server->ssh_private_key) {
            throw new RuntimeException("Server [{$server->id}] has no control plane SSH key installed yet.");
        }

        $this->client = new SFTP($server->ip_address, $server->ssh_port, 10);

        $key = PublicKeyLoader::load($server->ssh_private_key);

        if (! $this->client->login($user, $key)) {
            throw new RuntimeException("Unable to authenticate as {$user}@{$server->ip_address}.");
        }
    }

    /**
     * Connect as the server's day-to-day deploy user.
     */
    public static function for(Server $server): self
    {
        return new self($server, $server->ssh_user);
    }

    /**
     * Connect as root using the same control plane keypair, for the handful of
     * operations that genuinely need root (installing packages, writing Nginx
     * vhosts). Available because CreateDeployUser only disables root's
     * *password* authentication, not key-based login.
     */
    public static function forRoot(Server $server): self
    {
        return new self($server, 'root');
    }

    /**
     * Run a shell script, optionally streaming each output chunk as it arrives.
     */
    public function run(string $script, ?callable $onOutput = null): SshResult
    {
        $output = '';

        $this->client->exec($script, function (string $chunk) use (&$output, $onOutput) {
            $output .= $chunk;
            if ($onOutput !== null) {
                $onOutput($chunk);
            }
        });

        return new SshResult((int) $this->client->getExitStatus(), $output);
    }

    /**
     * Atomically write a file's contents to the given remote path.
     */
    public function writeFile(string $remotePath, string $contents, int $mode = 0644): void
    {
        $tempPath = $remotePath.'.tmp-'.uniqid();

        if (! $this->client->put($tempPath, $contents)) {
            throw new RuntimeException("Failed to write to {$tempPath}.");
        }

        $this->client->chmod($mode, $tempPath);

        if (! $this->client->rename($tempPath, $remotePath)) {
            throw new RuntimeException("Failed to move {$tempPath} into place at {$remotePath}.");
        }
    }

    public function sftp(): SFTP
    {
        return $this->client;
    }
}
