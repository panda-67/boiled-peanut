<?php

namespace App\Reports\Stocks;

use App\Models\ProductTransaction;

class ProductStockReport
{
    public function current()
    {
        return ProductTransaction::query()
            ->selectRaw('product_id, SUM(quantity) as stock')
            ->groupBy('product_id')
            ->with('product:id,name')
            ->get();
    }
}
