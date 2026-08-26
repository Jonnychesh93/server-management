<?php

namespace App\Services\Git;

use App\Enums\GitProvider;
use App\Models\GitConnection;

class GitConnectionProviderFactory
{
    public static function for(GitConnection $connection): GitConnectionProvider
    {
        return match ($connection->provider) {
            GitProvider::Manual => new ManualGitProvider($connection),
            GitProvider::GitHubApp => new GitHubAppProvider($connection),
        };
    }
}
