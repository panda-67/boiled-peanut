<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\BusinessDay;
use App\Models\User;

class BusinessDayPolicy
{
    public function open(User $user): bool
    {
        return $user->whomActAs(UserRole::MANAGER, UserRole::OWNER);
    }

    public function close(User $user, BusinessDay $businessDay): bool
    {
        return $user->whomActAs(UserRole::MANAGER, UserRole::OWNER)
            && $businessDay->isOpen();
    }
}
