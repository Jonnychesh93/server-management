<?php

namespace App\Enums;

enum DeploymentTriggerType: string
{
    case User = 'user';
    case Webhook = 'webhook';
}
