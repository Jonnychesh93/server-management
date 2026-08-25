<?php

namespace App\Services\Provisioning\Steps;

class InstallFirewall extends AptStep
{
    public function name(): string
    {
        return 'install_firewall';
    }

    protected function commands(): string
    {
        return <<<'BASH'
            apt-get install -y ufw
            ufw allow OpenSSH
            ufw allow 80/tcp
            ufw allow 443/tcp
            ufw --force enable
            BASH;
    }
}
