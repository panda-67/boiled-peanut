<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Location;
use App\Models\Material;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function get(?string $type, ?string $locationId)
    {
        $products = Product::select('id', 'name', 'selling_price')->get();

        $locations = Location::where('is_active', true)
            ->when($type, fn($q, $t) => $q->where('type', $t))
            ->when($locationId, fn($q, $id) => $q->where('_id', $id))
            ->get(['id', '_id', 'name']);

        $transactions = DB::table('product_transactions')
            ->select(
                'product_id',
                'location_id',
                DB::raw("
                    SUM(CASE
                        WHEN type IN ('IN','OUT') THEN quantity
                        ELSE 0
                    END) as stock
                "),
                DB::raw("
                    SUM(CASE
                        WHEN type = 'RESERVE' THEN quantity
                        ELSE 0
                    END) as reserved
                ")
            )
            ->when($locationId, function ($q) use ($locations) {
                $q->where('location_id', $locations->first()->id);
            })
            ->groupBy('product_id', 'location_id')
            ->get()
            ->groupBy('product_id');

        $result = collect([]);

        foreach ($products as $product) {

            $productLocations = collect([]);

            foreach ($locations as $location) {

                $rows = $transactions[$product->id] ?? collect();

                $row = $rows->firstWhere('location_id', $location->id);

                $stock = (float) ($row->stock ?? 0);
                $reserved = (float) ($row->reserved ?? 0);

                $productLocations->push([
                    'id'        => $location->_id,
                    'name'      => $location->name,
                    'stock'     => $stock,
                    'reserved'  => $reserved,
                    'available' => $stock - $reserved,
                ]);
            }

            $result->push([
                'id'        => $product->id,
                'name'      => $product->name,
                'price'     => $product->selling_price,
                'locations' => $productLocations,
            ]);
        }

        $materials = collect([]);

        if ($type === 'central' && $locations->isNotEmpty()) {

            $materialModels = Material::where('is_stocked', true)->get();

            foreach ($materialModels as $material) {

                $materialLocations = collect([]);

                foreach ($locations as $location) {

                    $stock = (float) $material->stockAt($location);

                    $materialLocations->push([
                        'id'        => $location->_id,
                        'name'      => $location->name,
                        'available' => $stock,
                    ]);
                }

                $materials->push([
                    'id'        => $material->id,
                    'name'      => $material->name,
                    'price'     => $material->default_unit_cost,
                    'locations' => $materialLocations,
                ]);
            }

            $result = $result->concat($materials)->values();
        }

        return $result;
    }
}
