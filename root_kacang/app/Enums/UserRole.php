<?php

namespace App\Enums;

enum UserRole: string
{
    case SU         = 'su';
    case OWNER      = 'owner';
    case MANAGER    = 'manager';
    case OPERATOR   = 'operator';
    case SPECTATOR  = 'spectator';
}
