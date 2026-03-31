<?php

namespace Database\Seeders;

use App\Enums\ItemType;
use App\Models\Item;
use App\Models\Location;
use App\Models\StockMovement;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Pastikan location MainStore ada
        $main = Location::firstWhere([
            'name' => 'Main Store',
        ]);

        $second = Location::firstWhere([
            'name' => 'Second Store'
        ]);

        $locations = collect([$main, $second]);

        // 2. Buat 2 produk
        $products = Item::factory()
            ->count(2)
            ->sequence(
                [
                    'name' => 'Kacang Rebus',
                    'default_price' => 7000,
                    'type' => ItemType::FINISHED,
                    'is_sellable' => true
                ],
                [
                    'name' => 'Ubi Goreng',
                    'default_price' => 5000,
                    'type' => ItemType::FINISHED,
                    'is_sellable' => true
                ],
            )
            ->create();

        // 3. Buat initial stock transaction (ledger-based)
        $transactions = [];

        foreach ($locations as $location) {
            foreach ($products as $product) {
                $transactions[] = [
                    'item_id'        => $product->id,
                    'location_id'    => $location->id,
                    'date'           => now(),
                    'type'           => 'in',
                    'quantity'       => 100,
                    'reference_type' => 'transfer',
                    'reference_id'   => (string) Str::uuid(),
                    'note'           => 'Initial stock seeding',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];
            }
        }

        StockMovement::insert($transactions);
    }
}
