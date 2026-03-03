<?php

namespace App\Domain\Guards;

use App\Models\User;
use App\Enums\LocationType;
use DomainException;

final class LocationGuard
{
    public static function ensureSalePoint(User $user): void
    {
        $assignment = $user->activeLocation
            ?? $user->managerActiveLocation
            ?? $user->ownerActiveLocation;

        if (! $assignment) {
            throw new DomainException('USER_HAS_NO_ACTIVE_LOCATION');
        }

        if ($assignment->location->type !== LocationType::SALE_POINT) {
            throw new DomainException('This action must be on sale point.');
        }
    }
}
