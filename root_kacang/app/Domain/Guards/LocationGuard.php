<?php

namespace App\Domain\Guards;

use App\Models\User;
use App\Enums\LocationType;
use DomainException;

final class LocationGuard
{
    public static function ensureSalePoint(User $user): void
    {
        if (!$assignment = $user->activeLocation) {
            throw new DomainException('USER_HAS_NO_ACTIVE_LOCATION');
        }

        if ($assignment->location->type !== LocationType::SALE_POINT->value) {
            throw new DomainException('CONFIRM_SALE_INVALID_LOCATION');
        }
    }
}
