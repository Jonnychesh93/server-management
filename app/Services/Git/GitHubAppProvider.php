<?php

namespace App\Services\Git;

use App\Models\GitConnection;
use App\Services\GitHub\GitHubAppClient;
use RuntimeException;

/**
 * A repository connected via the GitHub App installation: no deploy key
 * needed, cloned over HTTPS with a short-lived installation access token
 * embedded in the URL.
 */
class GitHubAppProvider implements GitConnectionProvider
{
    public function __construct(private readonly GitConnection $connection) {}

    public function deployPrivateKey(): ?string
    {
        return null;
    }

    public function repository(): string
    {
        if (! $this->connection->installation_id) {
            throw new RuntimeException('This git connection has no GitHub App installation.');
        }

        $token = app(GitHubAppClient::class)->installationToken((int) $this->connection->installation_id);

        return "https://x-access-token:{$token}@github.com/{$this->connection->repository}.git";
    }

    public function branch(): string
    {
        return $this->connection->branch;
    }
}
