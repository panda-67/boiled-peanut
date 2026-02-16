<?php

namespace App\Policies;

use App\Models\BusinessDay;
use App\Models\User;

class SettlementPolicy
{
    public function create(User $user, BusinessDay $day): bool
    {
        return $day->isClosed()
            && !$day->isSettled()
            && $user->hasRoleAtLocation('manager');
    }

    public function view(User $user, BusinessDay $day): bool
    {
        return $user->canAccessLocation($day->location_id);
    }
}
