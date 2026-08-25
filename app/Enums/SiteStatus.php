<?php

namespace App\Enums;

enum SiteStatus: string
{
    case Provisioning = 'provisioning';
    case Active = 'active';
    case Failed = 'failed';
    case Disabled = 'disabled';
}
