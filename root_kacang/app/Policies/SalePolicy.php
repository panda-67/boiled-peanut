<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Sale;
use App\Services\Context\ActiveContext;

class SalePolicy
{
    public function create(User $user, ActiveContext $context): bool
    {
        return $context->businessDay->isOpen()
            && $user->whomActAs(UserRole::OPERATOR, UserRole::MANAGER, UserRole::OWNER);
    }

    public function addItem(User $user, Sale $sale, ActiveContext $context): bool
    {
        return $context->businessDay->isOpen()
            && $sale->status->canBeEdit()
            && $user->whomActAs(UserRole::OPERATOR);
    }

    public function removeItem(User $user, Sale $sale, ActiveContext $context): bool
    {
        return $context->businessDay->isOpen()
            && $sale->status->canBeEdit()
            && $user->whomActAs(UserRole::OPERATOR);
    }

    public function confirm(User $user, Sale $sale, ActiveContext $context): bool
    {
        return $context->businessDay->isOpen()
            && $sale->business_day_id === $context->businessDay->id
            && $sale->location_id === $context->location->id
            && $sale->status->canBeConfirmed()
            && $user->whomActAs(UserRole::OPERATOR);
    }

    public function cancel(User $user, Sale $sale, ActiveContext $context): bool
    {
        return $context->businessDay->isOpen()
            && $sale->status->canBeCancelled()
            && $user->whomActAs(UserRole::MANAGER);
    }

    public function settle(User $user, Sale $sale, ActiveContext $context): bool
    {
        return $sale->business_day_id === $context->businessDay->id
            && $sale->status->canBeSettled()
            && $user->whomActAs(UserRole::MANAGER);
    }
}
