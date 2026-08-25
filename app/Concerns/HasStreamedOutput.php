<?php

namespace App\Concerns;

trait HasStreamedOutput
{
    /**
     * Append a chunk of streamed command output to the given column and persist it
     * immediately, so a crashed job still leaves partial output behind.
     */
    public function appendOutput(string $column, string $chunk): void
    {
        $this->{$column} = ($this->{$column} ?? '').$chunk;
        $this->save();
    }
}
