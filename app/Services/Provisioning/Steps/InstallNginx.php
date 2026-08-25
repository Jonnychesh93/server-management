<?php

namespace App\Services\Provisioning\Steps;

class InstallNginx extends AptStep
{
    public function name(): string
    {
        return 'install_nginx';
    }

    protected function commands(): string
    {
        return <<<'BASH'
            apt-get install -y nginx
            systemctl enable nginx
            systemctl start nginx
            BASH;
    }
}
