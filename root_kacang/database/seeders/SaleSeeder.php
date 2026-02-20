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
        $store = Location::firstWhere('name', 'Main Store');
        $secondStore = Location::firstWhere('name', 'Second Store');

        Sale::factory()->atLocation($store)->create();
        Sale::factory()->atLocation($secondStore)->create();
    }
}
