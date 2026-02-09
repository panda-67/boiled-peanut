<?php

namespace App\Enums;

enum ProductTransactionType: string
{
    case IN  = 'in';
    case OUT = 'out';
    case RESERVE = 'reserve';
    case RELEASE = 'release';
    case SETTLE = 'settle';
}
