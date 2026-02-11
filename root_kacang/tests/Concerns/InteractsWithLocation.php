<?php

namespace Tests\Concerns;

use App\Models\Location;
use App\Models\User;
use App\Models\UserLocationAssignment;

trait InteractsWithLocation
{
    protected Location $central;
    protected Location $salesPoint;

    protected function setUpLocations(): void
    {
        $this->central = \App\Models\Location::factory()->central()->create();
        $this->salesPoint = \App\Models\Location::factory()->salesPoint()->create();
    }

    protected function assignUserToLocation(User $user, Location $location): void
    {
        UserLocationAssignment::where('user_id', $user->id)
            ->whereNull('active_to')
            ->update(['active_to' => now()]);

        UserLocationAssignment::create([
            'user_id'     => $user->id,
            'location_id' => $location->id,
            'active_from' => now(),
            'active_to'   => null,
        ]);
    }
}
