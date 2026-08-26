<?php

namespace App\Services\Ssh;

use App\Enums\BootstrapCredentialType;
use App\Models\Server;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;
use RuntimeException;

/**
 * One-time-use connection authenticated with the credential the user supplied
 * when adding a server. Its only job is to install the control plane's own
 * keypair, so no other part of the application ever touches that credential.
 */
class BootstrapConnection
{
    private SSH2 $client;

    public function __construct(private readonly Server $server)
    {
        // See SshConnection for why this is 0, not a short fixed timeout.
        $this->client = new SSH2($server->ip_address, $server->ssh_port, 0);

        $credential = $server->bootstrap_credential_type === BootstrapCredentialType::PrivateKey
            ? PublicKeyLoader::load((string) $server->bootstrap_credential)
            : $server->bootstrap_credential;

        if (! $this->client->login($server->ssh_user, $credential)) {
            throw new RuntimeException("Unable to authenticate with the supplied credential for {$server->ip_address}.");
        }
    }

    /**
     * Generate (if needed) the control plane's keypair for this server, install
     * it into authorized_keys, and return a verified SshConnection using it.
     */
    public function installControlPlaneKey(): SshConnection
    {
        if (! $this->server->ssh_private_key) {
            $key = KeyPairGenerator::generateEd25519();

            $this->server->forceFill([
                'ssh_private_key' => $key['private'],
                'ssh_public_key' => $key['public'],
            ])->save();
        }

        $publicKey = $this->server->ssh_public_key;

        $script = <<<SCRIPT
            mkdir -p ~/.ssh && chmod 700 ~/.ssh
            touch ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys
            grep -qxF '{$publicKey}' ~/.ssh/authorized_keys || echo '{$publicKey}' >> ~/.ssh/authorized_keys
            SCRIPT;

        $output = '';
        $this->client->exec($script, function (string $chunk) use (&$output) {
            $output .= $chunk;
        });

        if ((int) $this->client->getExitStatus() !== 0) {
            throw new RuntimeException("Failed to install the control plane key: {$output}");
        }

        return SshConnection::for($this->server);
    }
}
