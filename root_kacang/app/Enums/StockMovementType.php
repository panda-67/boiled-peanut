<?php

namespace App\Enums;

enum StockMovementType: string
{
    case IN = 'IN';
    case OUT = 'OUT';
    case RESERVE = 'reserve';
    case RELEASE = 'release';
    case SETTLE = 'settle';
}
