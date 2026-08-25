<?php

namespace App\Enums;

enum GitProvider: string
{
    case Manual = 'manual';
    case GitHubApp = 'github_app';
}
