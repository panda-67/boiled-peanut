<?php

namespace App\Policies;

use App\Models\User;

class ProductionPolicy
{
    public function create(User $user): bool
    {
        $day = app('activeBusinessDay');

        return $day
            && $day->isOpen()
            && $user->hasRoleAtLocation(['operator', 'manager']);
    }

    public function confirm(User $user): bool
    {
        return $user->hasRoleAtLocation('manager');
    }
}
