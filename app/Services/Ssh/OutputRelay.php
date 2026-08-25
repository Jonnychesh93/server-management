<?php

namespace App\Services\Ssh;

use Closure;

/**
 * Shared streaming primitive for every job that runs a remote script: persists
 * each output chunk immediately (so a crashed job still leaves partial output
 * on disk) and broadcasts it for live viewers, tagging each chunk with a
 * monotonically increasing sequence number.
 */
final class OutputRelay
{
    private int $sequence = 0;

    /**
     * @param  Closure(string): void  $persist
     * @param  Closure(string, int): void  $broadcast
     */
    public function __construct(
        private readonly Closure $persist,
        private readonly Closure $broadcast,
    ) {}

    public function __invoke(string $chunk): void
    {
        ($this->persist)($chunk);
        ($this->broadcast)($chunk, ++$this->sequence);
    }
}
