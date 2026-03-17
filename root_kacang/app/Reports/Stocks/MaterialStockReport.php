<?php

namespace App\Reports\Stocks;

use Illuminate\Support\Facades\DB;

class MaterialStockReport
{
    public function current()
    {
        return DB::table('stock_movements')
            ->selectRaw('material_id, SUM(quantity) as stock')
            ->groupBy('material_id')
            ->get();
    }
}
