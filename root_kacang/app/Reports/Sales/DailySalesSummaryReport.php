<?php

namespace App\Reports\Sales;

use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Reports\BaseReport;
use Carbon\Carbon;

class DailySalesSummaryReport extends BaseReport
{
    public function forDate(Carbon $date, ?string $locationId = null)
    {
        return Sale::query()
            ->whereHas('businessDay', function ($q) use ($date, $locationId) {
                $q->whereDate('date', $date)
                    ->when($locationId, fn($q) => $q->where('location_id', $locationId));
            })
            ->where('status', SaleStatus::SETTLED)
            ->selectRaw('
                COUNT(*) as transactions,
                SUM(total) as total_sales
            ')
            ->first();
    }

    public function between(Carbon $start, Carbon $end, ?string $locationId = null)
    {
        return $this->salesBaseQuery($start, $end, $locationId)
            ->selectRaw('
                COUNT(sales.id) as transactions,
                SUM(sales.total) as total_sales,
                AVG(sales.total) as avg_transaction
            ')
            ->first();
    }
}
