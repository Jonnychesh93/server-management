<?php

namespace App\Enums;

enum CronStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Failed = 'failed';
}
