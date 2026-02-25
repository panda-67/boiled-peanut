<?php

namespace Database\Seeders;

use App\Models\BusinessDay;
use App\Models\Location;
use App\Models\User;
use App\Models\UserLocationAssignment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BusinessDaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@kacang.test')->first();
        $budi = User::where('email', 'budi@kacang.test')->first();
        $store = Location::firstWhere('name', 'Main Store');
        $secondStore = Location::firstWhere('name', 'Second Store');

        BusinessDay::factory()->forLocation($store)->create([
            'opened_by' => $admin->id
        ]);

        BusinessDay::factory()->forLocation($secondStore)->create([
            'opened_by' => $admin->id
        ]);

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
