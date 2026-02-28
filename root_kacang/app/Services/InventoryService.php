<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function get(?string $locationId)
    {
        $products = Product::select('id', 'name', 'selling_price')->get();

        $locations = $locationId
            ? Location::where('is_active', true)->where('_id', $locationId)->get()
            : Location::where('is_active', true)->select('id', '_id', 'name')->get();

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

        $result = [];

        foreach ($products as $product) {

            $productLocations = [];

            foreach ($locations as $location) {

                $rows = $transactions[$product->id] ?? collect();

                $row = $rows->firstWhere('location_id', $location->id);

                $stock = (float) ($row->stock ?? 0);
                $reserved = (float) ($row->reserved ?? 0);

                $productLocations[] = [
                    'id' => $location->_id,
                    'name' => $location->name,
                    'stock' => $stock,
                    'reserved' => $reserved,
                    'available' => $stock - $reserved,
                ];
            }

            $result[] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->selling_price,
                'locations' => $productLocations,
            ];
        }

        return $result;
    }
}
