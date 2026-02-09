<?php

namespace App\Enums;

enum LocationType: string
{
    case CENTRAL = 'central';
    case SALE_POINT = 'sale_point';
    case WAREHOUSE = 'warehouse';
}
