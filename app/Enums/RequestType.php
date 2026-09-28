<?php

namespace App\Enums;

enum RequestType: string
{
    case Broadcast = 'broadcast';
    case Direct = 'direct';
}
