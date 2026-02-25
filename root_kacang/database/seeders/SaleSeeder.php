<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Sale;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $store = Location::where('name', 'Main Store')->with('openBusinessDay')->first();
        $secondStore = Location::where('name', 'Second Store')->with('openBusinessDay')->first();

        Sale::factory()->atLocation($store)->create([
            'business_day_id' => $store->openBusinessDay->id,
        ]);

        Sale::factory()->atLocation($secondStore)->create([
            'business_day_id' => $secondStore->openBusinessDay->id,
        ]);
    }
}
