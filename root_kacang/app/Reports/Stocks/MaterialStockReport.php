<?php

namespace App\Reports\Stocks;

use App\Models\StockMovement;

class MaterialStockReport
{
    public function current()
    {
        return StockMovement::query()
            ->selectRaw('material_id, SUM(quantity) as stock')
            ->groupBy('material_id')
            ->with('material:id,name,unit')
            ->get();
    }
}
