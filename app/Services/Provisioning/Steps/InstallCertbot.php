<?php

namespace App\Services\Provisioning\Steps;

class InstallCertbot extends AptStep
{
    public function name(): string
    {
        return 'install_certbot';
    }

    protected function commands(): string
    {
        return <<<'BASH'
            apt-get install -y certbot python3-certbot-nginx
            BASH;
    }
}
