<?php

namespace App\Enums;

enum SaleStatus: string
{
    case DRAFT = 'draft';
    case CONFIRMED = 'confirmed';
    case SETTLED = 'settled';
    case CANCELLED = 'cancelled';

    public function canBeEdit(): bool
    {
        return $this === self::DRAFT;
    }

    public function canBeConfirmed(): bool
    {
        return $this === self::DRAFT;
    }

    public function canBeSettled(): bool
    {
        return $this === self::CONFIRMED;
    }

    public function canBeCancelled(): bool
    {
        return $this === self::CONFIRMED;
    }
}
