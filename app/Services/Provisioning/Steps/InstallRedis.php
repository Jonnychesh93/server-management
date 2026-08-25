<?php

namespace App\Services\Provisioning\Steps;

class InstallRedis extends AptStep
{
    public function name(): string
    {
        return 'install_redis';
    }

    protected function commands(): string
    {
        return <<<'BASH'
            apt-get install -y redis-server
            systemctl enable redis-server
            systemctl start redis-server
            BASH;
    }
}
