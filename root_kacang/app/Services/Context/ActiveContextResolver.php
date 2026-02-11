<?php

namespace App\Services\Context;

use App\Models\BusinessDay;
use App\Models\User;

class ActiveContextResolver
{
    public function resolveForUser(User $user): ActiveContext
    {
        if (!$user->activeLocation) {
            throw new \DomainException('USER_HAS_NO_ACTIVE_LOCATION');
        }

        if (!$user->activeLocation->location->is_active) {
            throw new \DomainException('LOCATION_INACTIVE');
        }

        $businessDay = BusinessDay::activeFor($user->activeLocation->location->id);

        if (!$businessDay) {
            throw new \DomainException('NO_ACTIVE_BUSINESS_DAY');
        }

        return new ActiveContext(
            $user->activeLocation->id,
            $businessDay
        );
    }
}
