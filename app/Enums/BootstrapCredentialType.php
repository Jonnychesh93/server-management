<?php

namespace App\Enums;

enum BootstrapCredentialType: string
{
    case Password = 'password';
    case PrivateKey = 'private_key';
}
