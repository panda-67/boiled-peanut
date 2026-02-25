<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Product;
use App\Models\ProductTransaction;
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
        $products = Product::factory()
            ->count(2)
            ->sequence(
                ['name' => 'Kacang Rebus', 'selling_price' => 7000],
                ['name' => 'Ubi Goreng', 'selling_price' => 5000],
            )
            ->create();

        // 3. Buat initial stock transaction (ledger-based)
        $transactions = [];

        foreach ($locations as $location) {
            foreach ($products as $product) {
                $transactions[] = [
                    'product_id'     => $product->id,
                    'location_id'    => $location->id,
                    'date'           => now(),
                    'type'           => 'in',
                    'quantity'       => 100,
                    'reference_type' => 'production',
                    'reference_id'   => (string) Str::uuid(),
                    'note'           => 'Initial stock seeding',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];
            }
        }

        ProductTransaction::insert($transactions);
    }
}
