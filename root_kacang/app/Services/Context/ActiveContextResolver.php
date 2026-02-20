<?php

namespace App\Services\Context;

use App\Enums\UserRole;
use App\Models\BusinessDay;
use App\Models\Location;
use App\Models\User;
use DomainException;

class ActiveContextResolver
{
    public function resolveForUser(User $user): ActiveContext
    {
        $location = $this->resolveLocation($user);

        if (!$location->is_active) {
            throw new DomainException('LOCATION_INACTIVE');
        }

        $businessDay = BusinessDay::activeFor($location->id);

        if (!$businessDay) {
            throw new DomainException('NO_ACTIVE_BUSINESS_DAY');
        }

        return new ActiveContext($location, $businessDay);
    }

    protected function resolveLocation(User $user): Location
    {
        if ($user->whomActAs(UserRole::MANAGER)) {

            $context = $user->managerActiveLocation;

            if (!$context) {
                throw new DomainException('NO_ACTIVE_LOCATION_SELECTED');
            }

            return $context->location;
        }

        // Default: assigned active location (operator logic)
        if (!$user->activeLocation) {
            throw new DomainException('USER_HAS_NO_ACTIVE_LOCATION');
        }

        return $user->activeLocation->location;
    }
}
