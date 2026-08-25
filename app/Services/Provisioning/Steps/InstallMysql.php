<?php

namespace App\Services\Provisioning\Steps;

class InstallMysql extends AptStep
{
    public function name(): string
    {
        return 'install_mysql';
    }

    protected function commands(): string
    {
        return <<<'BASH'
            apt-get install -y mysql-server
            systemctl enable mysql
            systemctl start mysql
            BASH;
    }
}
