<?php

namespace App\Services\Provisioning\Steps;

class UpdatePackages extends AptStep
{
    public function name(): string
    {
        return 'update_packages';
    }

    protected function commands(): string
    {
        return <<<'BASH'
            apt-get update -y
            apt-get upgrade -y
            apt-get install -y software-properties-common curl gnupg2 ca-certificates lsb-release apt-transport-https unzip
            BASH;
    }
}
