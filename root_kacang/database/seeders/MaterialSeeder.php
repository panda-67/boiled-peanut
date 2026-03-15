<?php

namespace Database\Seeders;

use App\Enums\ReferenceType;
use App\Enums\StockMovementType;
use App\Models\Location;
use App\Models\Material;
use App\Models\StockMovement;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $central = Location::where('name', 'Central Kitchen')->first();

        if (!$central) {
            throw new \Exception('Central Kitchen location not found.');
        }

        $materials = Material::factory()->count(4)
            ->sequence(
                ['name' => 'Kacang Mentah', 'default_unit_cost' => 12000, 'unit' => 'Kg'],
                ['name' => 'Garam', 'default_unit_cost' => 1200, 'unit' => 'Ons'],
                ['name' => 'Air', 'default_unit_cost' => 2000, 'unit' => 'Liter', 'is_stocked' => false],
                ['name' => 'Gas', 'default_unit_cost' => 18000, 'unit' => 'Tabung', 'is_stocked' => false],
            )->create();

        foreach ($materials as $material) {
            StockMovement::create([
                'material_id' => $material->id,
                'location_id' => $central->id,
                'quantity' => 100,
                'type' => StockMovementType::IN,
                'reference_type' => ReferenceType::INITIAL,
                'reference_id' => 1,
            ]);
        }
    }
}
