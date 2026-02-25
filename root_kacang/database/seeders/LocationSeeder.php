<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\User;
use App\Models\UserLocationAssignment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Location::factory()->central()->create();
        Location::factory()->salesPoint()->create(['name' => 'Main Store']);
        Location::factory()->salesPoint()->create(['name' => 'Second Store']);
        $location = Location::factory()->count(3)->create();

        $admin = User::firstWhere('name', 'Admin');

        $location->each(function ($loc) use ($admin) {
            UserLocationAssignment::create([
                'user_id'     => $admin->id,
                'location_id' => $loc->id,
                'active_from' => now(),
                'active_to'   => null,
            ]);
        });
    }
}
