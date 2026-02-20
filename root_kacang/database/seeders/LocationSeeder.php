<?php

namespace Database\Seeders;

use App\Models\BusinessDay;
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
        $store = Location::factory()->salesPoint()->create([
            'name' => 'Main Store',
        ]);

        $secondStore = Location::factory()->salesPoint()->create([
            'name' => 'Second Store',
        ]);

        $admin = User::where('email', 'admin@kacang.test')->first();
        $budi = User::where('email', 'budi@kacang.test')->first();

        BusinessDay::factory()->forLocation($store)->create();
        BusinessDay::factory()->forLocation($secondStore)->create();

        UserLocationAssignment::create([
            'user_id'     => $admin->id,
            'location_id' => $secondStore->id,
            'active_from' => now(),
            'active_to'   => null,
        ]);

        UserLocationAssignment::create([
            'user_id'     => $admin->id,
            'location_id' => $store->id,
            'active_from' => now(),
            'active_to'   => null,
        ]);

        UserLocationAssignment::create([
            'user_id'     => $budi->id,
            'location_id' => $store->id,
            'active_from' => now(),
            'active_to'   => null,
        ]);
    }
}
