<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\ProductStock;

final class StockReservationService
{
    public function reserveForSale(Sale $sale): void
    {
        foreach ($sale->items as $item) {
            ProductStock::where([
                'product_id' => $item->product_id,
                'location_id' => $sale->location_id,
            ])->increment('reserved_qty', $item->quantity);
        }
    }
}
