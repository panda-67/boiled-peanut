<?php

namespace App\Reports\Sales;

use App\Models\DailyCogs;
use App\Models\Sale;
use Illuminate\Support\Carbon;

class DailyProfitReport
{
    public function forDate(Carbon $date)
    {
        $sales = Sale::whereDate('sale_date', $date)
            ->where('status', 'settled')
            ->sum('total');

        $cogs = DailyCogs::where('date', $date)
            ->value('cogs_amount');

        return [
            'revenue' => $sales,
            'cogs' => $cogs,
            'gross_profit' => $sales - $cogs,
        ];
    }
}
