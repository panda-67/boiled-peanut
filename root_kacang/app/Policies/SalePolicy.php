<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Sale;

class SalePolicy
{
    public function create(User $user): bool
    {
        $day = app('activeBusinessDay');

        return $day
            && $day->isOpen()
            && $user->hasRoleAtLocation(['operator', 'manager']);
    }

    public function void(User $user, Sale $sale): bool
    {
        $day = app('activeBusinessDay');

        return $day
            && $day->isOpen()
            && $user->hasRoleAtLocation('manager');
    }

    public function confirm(User $user, Sale $sale): bool
    {
        $day = app('activeBusinessDay');

        return $day
            && $day->isOpen()
            && $sale->status->canBeConfirmed()
            && $sale->location_id === $day->location_id;
    }

    public function settle(User $user, Sale $sale): bool
    {
        $day = app('activeBusinessDay');

        return $day
            && $day->isClosed()
            && $sale->business_day_id === $day->id;
    }
}
