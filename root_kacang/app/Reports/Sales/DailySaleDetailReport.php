<?php

namespace App\Reports\Sales;

use App\Models\Sale;
use Illuminate\Support\Carbon;

class DailySalesDetailReport
{
    public function forDate(Carbon $date)
    {
        return Sale::query()
            ->with('items.product')
            ->whereDate('sale_date', $date)
            ->select('id', 'sale_date', 'status', 'total')
            ->orderBy('sale_date')
            ->get();
    }
}
