<?php

namespace App\Services\Git;

use App\Models\GitConnection;

/**
 * A user-supplied git URL, authenticated with a deploy key we generated and
 * the user added to their repository themselves.
 */
class ManualGitProvider implements GitConnectionProvider
{
    public function __construct(private readonly GitConnection $connection) {}

    public function deployPrivateKey(): ?string
    {
        return $this->connection->deployKey?->private_key;
    }

    public function repository(): string
    {
        return $this->connection->repository;
    }

    public function branch(): string
    {
        return $this->connection->branch;
    }
}
