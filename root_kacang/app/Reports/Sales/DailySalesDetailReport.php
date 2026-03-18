<?php

namespace App\Reports\Sales;

use App\Reports\BaseReport;
use Illuminate\Support\Carbon;

class DailySalesDetailReport extends BaseReport
{
    public function forDate(Carbon $start, Carbon $end)
    {
        return $this->salesBaseQuery($start, $end)
            ->with(['items.product', 'user'])
            ->orderBy('sale_date')
            ->get();
    }
}
