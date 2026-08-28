<?php

namespace App\Services\Provisioning\Steps;

class InstallNode extends AptStep
{
    public function name(): string
    {
        return 'install_node';
    }

    protected function commands(): string
    {
        return <<<'BASH'
            curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
            apt-get install -y nodejs
            BASH;
    }
}
