<?php

namespace App\Enums;

enum ServerConnectionStatus: string
{
    case Online = 'online';
    case Offline = 'offline';
    case Unknown = 'unknown';
}
