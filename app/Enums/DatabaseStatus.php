<?php

namespace App\Enums;

enum DatabaseStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Failed = 'failed';
}
