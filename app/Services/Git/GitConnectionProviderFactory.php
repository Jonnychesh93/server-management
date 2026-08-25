<?php

namespace App\Services\Git;

use App\Enums\GitProvider;
use App\Models\GitConnection;
use RuntimeException;

class GitConnectionProviderFactory
{
    public static function for(GitConnection $connection): GitConnectionProvider
    {
        return match ($connection->provider) {
            GitProvider::Manual => new ManualGitProvider($connection),
            GitProvider::GitHubApp => throw new RuntimeException('The GitHub App git provider is not implemented yet.'),
        };
    }
}
