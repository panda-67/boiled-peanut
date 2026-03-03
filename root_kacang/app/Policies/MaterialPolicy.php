<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Context\ActiveContext;

class MaterialPolicy
{
    public function create(User $user, ActiveContext $context): bool
    {
        return $context->businessDay->isOpen()
            && $user->whomActAs(UserRole::OWNER, UserRole::MANAGER);
    }
}
