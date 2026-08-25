<?php

namespace App\Services\Provisioning\Steps;

use App\Models\Server;
use App\Services\Provisioning\ProvisioningStep;

/**
 * Base for steps that run apt-based shell commands: fails fast on the first
 * error and disables interactive prompts, since each step is its own
 * non-interactive shell invocation with no state carried over from the last.
 */
abstract class AptStep implements ProvisioningStep
{
    abstract protected function commands(): string;

    public function script(Server $server): string
    {
        return <<<SCRIPT
            set -e
            export DEBIAN_FRONTEND=noninteractive
            {$this->commands()}
            SCRIPT;
    }
}
