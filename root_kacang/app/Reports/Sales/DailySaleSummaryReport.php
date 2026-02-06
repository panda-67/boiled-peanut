<?php

namespace App\Reports\Sales;

use App\Models\Sale;
use Carbon\Carbon;

class DailySalesSummaryReport
{
    public function forDate(Carbon $date)
    {
        return Sale::query()
            ->whereDate('sale_date', $date)
            ->selectRaw('
                COUNT(*) as total_sales,
                SUM(total) as gross_sales,
                SUM(CASE WHEN status = "settled" THEN total ELSE 0 END) as settled_sales,
                SUM(CASE WHEN status = "confirmed" THEN total ELSE 0 END) as outstanding_sales
            ')
            ->first();
    }
}
