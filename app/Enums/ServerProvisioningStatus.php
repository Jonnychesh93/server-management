<?php

namespace App\Enums;

enum ServerProvisioningStatus: string
{
    case Pending = 'pending';
    case Connecting = 'connecting';
    case Installing = 'installing';
    case Active = 'active';
    case Failed = 'failed';
}
