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
            {$user} ALL=(ALL) NOPASSWD: /usr/bin/systemctl * php*-fpm
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
