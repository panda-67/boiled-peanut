<?php

namespace App\Enums;

enum ItemType: string
{
    case RAW = 'RAW';
    case FINISHED = 'FINISHED';
    case SEMI = 'SEMI';
}
