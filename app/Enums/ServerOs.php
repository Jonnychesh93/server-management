<?php

namespace App\Enums;

enum ServerOs: string
{
    case Ubuntu2204 = 'ubuntu-22.04';
    case Ubuntu2404 = 'ubuntu-24.04';

    /**
     * Get the human-readable label for this operating system.
     */
    public function label(): string
    {
        return match ($this) {
            self::Ubuntu2204 => 'Ubuntu 22.04 LTS',
            self::Ubuntu2404 => 'Ubuntu 24.04 LTS',
        };
    }
}
