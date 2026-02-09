<?php

namespace App\Enums;

enum ReferenceType: string
{
    case PURCHASE   = 'purchase';
    case PRODUCTION = 'production';
    case TRANSFER   = 'transfer';
    case SALE       = 'sale';
    case ADJUSTMENT = 'adjustment';
    case INITIAL    = 'initial';
}
