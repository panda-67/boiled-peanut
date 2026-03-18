<?php

namespace App\Reports\Sales;

use App\Enums\SaleStatus;
use App\Models\DailyCogs;
use App\Models\Sale;
use App\Reports\BaseReport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DailyProfitReport extends BaseReport
{
    public function forDate(Carbon $date, ?string $locationId = null)
    {
        $sales = Sale::query()
            ->whereHas('businessDay', function ($q) use ($date, $locationId) {
                $q->whereDate('date', $date)
                    ->when($locationId, fn($q) => $q->where('location_id', $locationId));
            })
            ->where('status', SaleStatus::SETTLED)
            ->sum('total');

        $cogs = DailyCogs::query()
            ->whereHas('businessDay', function ($q) use ($date, $locationId) {
                $q->whereDate('date', $date)
                    ->when($locationId, fn($q) => $q->where('location_id', $locationId));
            })
            ->sum('cogs_amount');

        return [
            'sales' => $sales,
            'cogs' => $cogs,
            'gross_profit' => $sales - $cogs
        ];
    }

    public function between(Carbon $start, Carbon $end, ?string $locationId = null)
    {
        $sales = $this->salesBaseQuery($start, $end, $locationId)
            ->sum('sales.total');

        $cogs = DB::table('daily_cogs')
            ->join('business_days', 'business_days.id', '=', 'daily_cogs.business_day_id')
            ->when($start, fn($q) => $q->whereDate('business_days.date', '>=', $start))
            ->when($end, fn($q) => $q->whereDate('business_days.date', '<=', $end))
            ->when($locationId, fn($q) => $q->where('business_days.location_id', $locationId))
            ->sum('daily_cogs.cogs_amount');

        return [
            'sales' => $sales,
            'cogs' => $cogs,
            'gross_profit' => $sales - $cogs
        ];
    }
}
