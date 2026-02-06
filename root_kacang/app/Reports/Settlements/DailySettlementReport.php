<?php

namespace App\Reports\Settlements;

use App\Models\Settlement;
use Illuminate\Support\Carbon;

class DailySettlementReport
{
    public function forDate(Carbon $date)
    {
        return Settlement::query()
            ->whereDate('recieved_at', $date)
            ->selectRaw('
                method,
                COUNT(*) as total_sales,
                SUM(amount_received) as total_amount
            ')
            ->groupBy('method')
            ->get();
    }
}
