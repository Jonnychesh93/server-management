<?php

namespace App\Services\Provisioning\Steps;

class InstallComposer extends AptStep
{
    public function name(): string
    {
        return 'install_composer';
    }

    protected function commands(): string
    {
        return <<<'BASH'
            curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
            BASH;
    }
}
