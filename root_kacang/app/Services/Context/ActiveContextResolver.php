<?php

namespace App\Services\Context;

use App\Enums\BusinessDayStatus;
use App\Enums\UserRole;
use App\Models\BusinessDay;
use App\Models\Location;
use App\Models\User;
use App\Services\BusinessDayService;
use DomainException;

class ActiveContextResolver
{
    public function __construct(
        protected BusinessDayService $service
    ) {}

    public function resolveCentralContext(User $user, ?string $locationId): ActiveContext
    {
        $central = Location::where('type', 'central')->when(
            $locationId,
            fn($q, $id) => $q->where('_id', $id)
        )->firstOrFail();

        if (!$central->is_active) {
            throw new DomainException('CENTRAL_LOCATION_INACTIVE');
        }

        $businessDay = BusinessDay::activeFor($central->id);

        if (!$businessDay) {
            $businessDay = $this->service->open($central->id, $user->id);
        }

        return new ActiveContext($central, $businessDay);
    }

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
        if ($user->whomActAs(UserRole::OWNER)) {
            return $user->ownerActiveLocation->location;
        }

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
