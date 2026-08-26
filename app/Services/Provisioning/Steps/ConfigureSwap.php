<?php

namespace App\Services\Provisioning\Steps;

use App\Models\Server;
use App\Services\Provisioning\ProvisioningStep;

/**
 * Adds a 2GB swapfile if none exists yet. Small VPS instances OOM-kill
 * MySQL, npm, and composer under memory pressure without one — observed
 * in practice provisioning the control-plane box itself.
 */
class ConfigureSwap implements ProvisioningStep
{
    public function name(): string
    {
        return 'configure_swap';
    }

    public function script(Server $server): string
    {
        return <<<'BASH'
            set -e
            if [ "$(swapon --show | wc -l)" -eq 0 ] && [ ! -f /swapfile ]; then
                fallocate -l 2G /swapfile
                chmod 600 /swapfile
                mkswap /swapfile
                swapon /swapfile
                echo '/swapfile none swap sw 0 0' >> /etc/fstab
            fi
            BASH;
    }
}
