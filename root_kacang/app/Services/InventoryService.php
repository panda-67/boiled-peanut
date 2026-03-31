<?php

namespace App\Services;

use App\Enums\ItemType;
use App\Models\Item;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function get(?string $type, ?string $locationId)
    {
        $products = Item::where('type', ItemType::FINISHED)
            ->select('id', 'name', 'unit', 'default_price')
            ->get();

        $locations = Location::where('is_active', true)
            ->when($type, fn($q, $t) => $q->where('type', $t))
            ->get(['id', '_id', 'name']);

        if ($locationId) {
            $locations = $locations->where('_id', $locationId)->values();
        }

        $locationIds = $locations->pluck('id');

        $transactions = DB::table('stock_movements')
            ->select(
                'item_id',
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
            ->when($locationId, function ($q) use ($locationIds) {
                $q->whereIn('location_id', $locationIds);
            })
            ->groupBy('item_id', 'location_id')
            ->get()
            ->groupBy('item_id');

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
                'unit'      => $product->unit,
                'price'     => $product->default_price,
                'locations' => $productLocations,
            ]);
        }

        $materials = collect([]);

        if ($type === 'central' && $locations->isNotEmpty()) {

            $materialModels = Item::where('type', ItemType::RAW)
                ->where('is_stocked', true)
                ->get();

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
                    'unit'      => $material->unit,
                    'price'     => $material->default_unit_cost,
                    'locations' => $materialLocations,
                ]);
            }

            $result = $result->concat($materials)->values();
        }

        return $result;
    }
}
