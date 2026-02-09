<?php

namespace App\Enums;

enum SaleStatus: string
{
    case DRAFT = 'draft';
    case CONFIRMED = 'confirmed';
    case SETTLED = 'settled';
    case CANCELLED = 'cancelled';

    public function canBeConfirmed(): bool
    {
        return $this === self::DRAFT;
    }
}
