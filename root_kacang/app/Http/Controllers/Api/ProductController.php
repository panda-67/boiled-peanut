<?php

namespace App\Http\Controllers\Api;

use App\Enums\ItemType;
use App\Enums\ProductTransactionType;
use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $locationId = $request->user()->location->id;

        return Item::query()
            ->where('type', ItemType::FINISHED)
            ->select([
                'items.id',
                'items.name',
                'items.default_price',
                'items.unit',
            ])
            ->selectSub(function ($q) use ($locationId) {
                $q->from('stock_movements')
                    ->whereColumn('item_id', 'items.id')
                    ->where('location_id', $locationId)
                    ->whereIn('type', [
                        StockMovementType::IN,
                        StockMovementType::OUT,
                    ])
                    ->selectRaw('COALESCE(SUM(quantity), 0)');
            }, 'stock')
            ->selectSub(function ($q) use ($locationId) {
                $q->from('stock_movements')
                    ->whereColumn('item_id', 'items.id')
                    ->where('location_id', $locationId)
                    ->where('type', StockMovementType::RESERVE)
                    ->selectRaw('COALESCE(SUM(quantity), 0)');
            }, 'reserved')
            ->latest()
            ->get()
            ->map(function ($product) {
                $stock = (float) $product->stock;
                $reserved = (float) $product->reserved;

                return [
                    'id'        => $product->id,
                    'name'      => $product->name,
                    'price'     => $product->default_price,
                    'unit'      => $product->unit,
                    'stock'     => $stock,
                    'available' => $stock - $reserved,
                ];
            });
    }
}
