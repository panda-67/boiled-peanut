<?php

namespace App\Reports\Sales;

use App\Enums\SaleStatus;
use App\Models\Sale;
use Illuminate\Support\Carbon;

class DailySalesDetailReport
{
    public function forDate(Carbon $date)
    {
        return Sale::query()
            ->with(['items.product', 'user'])
            ->whereHas('businessDay', fn($q) => $q->whereDate('date', $date))
            ->where('status', SaleStatus::SETTLED)
            ->orderBy('sale_date')
            ->get();
    }
}
