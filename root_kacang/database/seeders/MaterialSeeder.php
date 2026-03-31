<?php

namespace Database\Seeders;

use App\Enums\ItemType;
use App\Enums\ReferenceType;
use App\Enums\StockMovementType;
use App\Models\Item;
use App\Models\Location;
use App\Models\StockMovement;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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

        $materials = Item::factory()
            ->count(4)
            ->state(new Sequence(
                [
                    'name' => 'Kacang Mentah',
                    'default_unit_cost' => 12000,
                    'unit' => 'kg',
                    'type' => ItemType::RAW,
                ],
                [
                    'name' => 'Garam',
                    'default_unit_cost' => 1200,
                    'unit' => 'ons',
                    'type' => ItemType::RAW,
                ],
                [
                    'name' => 'Air',
                    'default_unit_cost' => 2000,
                    'unit' => 'liter',
                    'is_stocked' => false,
                    'type' => ItemType::RAW,
                ],
                [
                    'name' => 'Gas',
                    'default_unit_cost' => 18000,
                    'unit' => 'tabung',
                    'is_stocked' => false,
                    'type' => ItemType::RAW,
                ],
            ))
            ->create();

        foreach ($materials as $material) {
            if (!$material->is_stocked) {
                continue;
            }

            StockMovement::create([
                'item_id' => $material->id,
                'location_id' => $central->id,
                'date' => now(), // WAJIB (schema kamu butuh ini)
                'quantity' => 100,
                'type' => StockMovementType::IN,
                'reference_type' => ReferenceType::INITIAL,
                'reference_id' => Str::uuid(),
                'note' => 'Initial stock seeding',
            ]);
        }
    }
}
