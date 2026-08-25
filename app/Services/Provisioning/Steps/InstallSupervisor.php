<?php

namespace App\Services\Provisioning\Steps;

class InstallSupervisor extends AptStep
{
    public function name(): string
    {
        return 'install_supervisor';
    }

    protected function commands(): string
    {
        return <<<'BASH'
            apt-get install -y supervisor
            systemctl enable supervisor
            systemctl start supervisor
            BASH;
    }
}
