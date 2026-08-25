<?php

namespace App\Enums;

enum DeploymentStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Success = 'success';
    case Failed = 'failed';
}
