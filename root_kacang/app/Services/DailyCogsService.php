<?php

namespace App\Services;

use App\Models\DailyClosing;
use App\Models\DailyCogs;
use App\Models\Production;
use App\Models\ProductTransaction;
use Illuminate\Support\Carbon;

class DailyCogsService
{
    public function calculate(Carbon $date): DailyCogs
    {
        if (!DailyClosing::where('date', $date)->exists()) {
            throw new \Exception('Day not closed yet');
        }

        if (DailyCogs::where('date', $date)->exists()) {
            throw new \Exception('COGS already calculated');
        }

        // total inventory cost (from production)
        $totalCost = Production::sum('total_cost');
        $totalQty  = Production::sum('output_quantity');

        if ($totalQty === 0) {
            throw new \Exception('No production data');
        }

        $avgCost = intdiv($totalCost, $totalQty);

        // qty sold today
        $qtySold = abs(
            ProductTransaction::where('type', 'out')
                ->whereDate('date', $date)
                ->sum('quantity')
        );

        $cogs = $qtySold * $avgCost;

        return DailyCogs::create([
            'date' => $date,
            'quantity_sold' => $qtySold,
            'average_cost' => $avgCost,
            'cogs_amount' => $cogs,
        ]);
    }
}
