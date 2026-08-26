<?php

namespace App\Services\Provisioning\Steps;

use App\Models\Server;
use App\Services\Provisioning\ProvisioningStep;

/**
 * Creates an unprivileged deploy user, gives it passwordless sudo for only
 * the service-management commands it needs, copies the control plane's key
 * into its authorized_keys, and disables root password authentication.
 */
class CreateDeployUser implements ProvisioningStep
{
    private const USER = 'appuser';

    public function name(): string
    {
        return 'create_deploy_user';
    }

    public function script(Server $server): string
    {
        $user = self::USER;

        // sudo's parser rejects a wildcard argument followed by further
        // literal arguments (confirmed against a real sudoers file on the
        // target OS — "systemctl * php8.1-fpm" fails, but the same shape
        // with no wildcard at all, as used for nginx below, is accepted).
        // Both the action and the version have to be spelled out literally.
        $phpFpmRules = implode("\n", array_map(
            fn (string $version) => "{$user} ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload php{$version}-fpm, /usr/bin/systemctl restart php{$version}-fpm",
            InstallPhp::SUPPORTED_VERSIONS,
        ));

        return <<<BASH
            set -e
            id -u {$user} &>/dev/null || useradd --create-home --shell /bin/bash {$user}
            mkdir -p /home/{$user}/.ssh
            cp ~/.ssh/authorized_keys /home/{$user}/.ssh/authorized_keys
            chown -R {$user}:{$user} /home/{$user}/.ssh
            chmod 700 /home/{$user}/.ssh
            chmod 600 /home/{$user}/.ssh/authorized_keys
            cat > /etc/sudoers.d/{$user} <<'SUDOERS'
            {$user} ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload nginx, /usr/bin/systemctl restart nginx
            {$phpFpmRules}
            {$user} ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl *
            {$user} ALL=(ALL) NOPASSWD: /usr/bin/certbot *
            SUDOERS
            chmod 440 /etc/sudoers.d/{$user}
            visudo -cf /etc/sudoers.d/{$user}
            sed -i 's/^#\?PermitRootLogin.*/PermitRootLogin prohibit-password/' /etc/ssh/sshd_config
            sed -i 's/^#\?PasswordAuthentication.*/PasswordAuthentication no/' /etc/ssh/sshd_config
            systemctl reload ssh || systemctl reload sshd
            BASH;
    }
}
