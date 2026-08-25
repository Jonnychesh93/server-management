<?php

namespace App\Services\Ssh;

final class SshResult
{
    public function __construct(
        public readonly int $exitCode,
        public readonly string $output,
    ) {}

    public function successful(): bool
    {
        return $this->exitCode === 0;
    }
}
