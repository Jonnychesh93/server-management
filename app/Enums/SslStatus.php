<?php

namespace App\Enums;

enum SslStatus: string
{
    case None = 'none';
    case Pending = 'pending';
    case Active = 'active';
    case Failed = 'failed';
}
