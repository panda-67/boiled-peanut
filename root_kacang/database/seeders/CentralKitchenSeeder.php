<?php

namespace Database\Seeders;

use App\Enums\ItemType;
use App\Enums\ReferenceType;
use App\Enums\StockMovementType;
use App\Models\Item;
use App\Models\Location;
use App\Models\StockMovement;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CentralKitchenSeeder extends Seeder
{
    public function run(): void
    {
        $central = Location::firstWhere('name', 'Central Kitchen');
        $products = Item::where('type', ItemType::FINISHED)->get();

        $products->each(function ($product) use ($central) {
            StockMovement::create([
                'item_id'     => $product->id,
                'location_id'    => $central->id,
                'date'           => now(),
                'type'           => StockMovementType::IN,
                'quantity'       => 1000,
                'reference_type' => ReferenceType::PRODUCTION,
                'reference_id'   => (string) Str::uuid(),
                'note'           => 'Initial stock seeding',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        });
    }
}
