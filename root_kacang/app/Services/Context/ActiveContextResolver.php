<?php

namespace App\Services\Context;

use App\Models\BusinessDay;
use App\Models\User;
use DomainException;

class ActiveContextResolver
{
    public function resolveForUser(User $user): ActiveContext
    {
        if (!$user->activeLocation) {
            throw new DomainException('USER_HAS_NO_ACTIVE_LOCATION');
        }

        $location = $user->activeLocation->location;

        if (!$location->is_active) {
            throw new DomainException('LOCATION_INACTIVE');
        }

        $businessDay = BusinessDay::activeFor($location->id);

        if (!$businessDay) {
            throw new DomainException('NO_ACTIVE_BUSINESS_DAY');
        }

        return new ActiveContext($location, $businessDay);
    }
}
