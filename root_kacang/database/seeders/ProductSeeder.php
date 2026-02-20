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
        $location = Location::firstWhere([
            'name' => 'Main Store',
        ]);

        // 2. Buat 2 produk
        $products = Product::factory()
            ->count(2)
            ->sequence(
                ['name' => 'Kacang Rebus', 'selling_price' => 7000],
                ['name' => 'Ubi Goreng', 'selling_price' => 5000],
            )
            ->create();

        // 3. Buat initial stock transaction (ledger-based)
        foreach ($products as $product) {
            ProductTransaction::create([
                'product_id'     => $product->id,
                'location_id'    => $location->id,
                'date'           => now(),
                'type'           => 'in', // positif
                'quantity'       => 100,
                'reference_type' => 'production',
                'reference_id'   => Str::uuid(),
                'note'           => 'Initial stock seeding',
            ]);
        }
    }
}
