<?php

namespace App\Services\Provisioning;

use App\Models\Server;

interface ProvisioningStep
{
    /**
     * A short, human-readable slug identifying this step (used to record where
     * provisioning failed).
     */
    public function name(): string;

    /**
     * The shell script to run for this step. Each step runs as its own
     * non-interactive shell invocation, so it must not depend on state
     * (exported variables, `cd`) set by a previous step.
     */
    public function script(Server $server): string;
}
