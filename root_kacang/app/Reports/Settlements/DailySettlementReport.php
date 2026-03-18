<?php

namespace App\Reports\Settlements;

use App\Models\Settlement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DailySettlementReport
{
    public function forDate(Carbon $date)
    {
        return Settlement::query()
            ->whereHas('sale.businessDay', function ($q) use ($date) {
                $q->whereDate('date', $date);
            })
            ->selectRaw("
                SUM(CASE WHEN method='cash' THEN amount_received ELSE 0 END) cash_total,
                SUM(CASE WHEN method='transfer' THEN amount_received ELSE 0 END) transfer_total,
                SUM(CASE WHEN method='ewallet' THEN amount_received ELSE 0 END) ewallet_total
            ")
            ->first();
    }

    public function between(Carbon $start, Carbon $end, ?string $locationId = null)
    {
        return DB::table('settlements')
            ->join('sales', 'sales.id', '=', 'settlements.sale_id')
            ->join('business_days', 'business_days.id', '=', 'sales.business_day_id')
            ->when($start, fn($q) => $q->whereDate('business_days.date', '>=', $start))
            ->when($end, fn($q) => $q->whereDate('business_days.date', '<=', $end))
            ->when($locationId, fn($q) => $q->where('business_days.location_id', $locationId))
            ->selectRaw("
                SUM(CASE WHEN method='cash' THEN amount_received ELSE 0 END) as cash_total,
                SUM(CASE WHEN method='transfer' THEN amount_received ELSE 0 END) as transfer_total,
                SUM(CASE WHEN method='ewallet' THEN amount_received ELSE 0 END) as ewallet_total
            ")
            ->first();
    }
}
