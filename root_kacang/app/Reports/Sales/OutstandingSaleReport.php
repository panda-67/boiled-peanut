<?php

namespace App\Reports\Sales;

use App\Models\Sale;

class OutstandingSalesReport
{
    public function all()
    {
        return Sale::query()
            ->where('status', 'confirmed')
            ->select('id', 'sale_date', 'total')
            ->orderBy('sale_date')
            ->get();
    }
}
