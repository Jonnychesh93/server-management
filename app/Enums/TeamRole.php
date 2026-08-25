<?php

namespace App\Enums;

enum TeamRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    /**
     * Determine if this role can manage servers, sites, and team membership.
     */
    public function canManage(): bool
    {
        return $this === self::Owner || $this === self::Admin;
    }
}
