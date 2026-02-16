<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\BusinessDay;
use App\Models\User;

class BusinessDayPolicy
{
    public function open(User $user, Location $location): bool
    {
        return $user->canAccessLocation($location)
            && $user->hasRoleAtLocation('manager')
            && !BusinessDay::activeFor($location->id);
    }

    public function close(User $user, BusinessDay $day): bool
    {
        return $user->hasRoleAtLocation('manager')
            && $day->isOpen()
            && $day->isBalanced();
    }

    public function view(User $user, BusinessDay $day): bool
    {
        return $user->canAccessLocation($day->location_id);
    }

    public function settle(User $user, BusinessDay $day): bool
    {
        return $user->hasRoleAtLocation('manager')
            && $day->isClosed()
            && !$day->isSettled();
    }
}
