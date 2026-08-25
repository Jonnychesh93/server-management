<?php

namespace App\Enums;

enum DaemonStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Failed = 'failed';
}
