<?php

namespace App\Enums;

enum LocationType: string
{
    case CENTRAL = 'central';
    case SALE_POINT = 'sales_point';
    case WAREHOUSE = 'warehouse';
}
