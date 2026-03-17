<?php

namespace App\Reports\Integrity;

use Illuminate\Support\Facades\DB;

class ProductionVsSalesReport
{
    public function summary()
    {
        return DB::table('productions')
            ->join('products', 'products.id', '=', 'productions.product_id')
            ->leftJoin('sale_items', 'sale_items.product_id', '=', 'products.id')
            ->selectRaw("
                products.name,
                SUM(productions.output_quantity) produced,
                SUM(sale_items.quantity) sold
            ")
            ->groupBy('products.name')
            ->get();
    }
}
