<?php

namespace App\Services\Git;

/**
 * Whatever a deployment needs to clone a site's repository, regardless of
 * how the connection to the git host is authenticated. A GitHubAppProvider
 * can be added later without RunDeploymentJob changing at all.
 */
interface GitConnectionProvider
{
    /**
     * The private key to install on the target server for cloning, if any.
     */
    public function deployPrivateKey(): ?string;

    public function repository(): string;

    public function branch(): string;
}
