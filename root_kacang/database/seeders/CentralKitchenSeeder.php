<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Product;
use App\Models\ProductTransaction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CentralKitchenSeeder extends Seeder
{
    public function run(): void
    {
        $central = Location::firstWhere('name', 'Central Kitchen');
        $products = Product::all();

        $products->each(function ($product) use ($central) {
            ProductTransaction::create([
                'product_id'     => $product->id,
                'location_id'    => $central->id,
                'date'           => now(),
                'type'           => 'in',
                'quantity'       => 1000,
                'reference_type' => 'production',
                'reference_id'   => (string) Str::uuid(),
                'note'           => 'Initial stock seeding',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        });
    }
}
