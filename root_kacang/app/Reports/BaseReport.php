<?php

namespace App\Reports;

use App\Enums\BusinessDayStatus;
use App\Enums\SaleStatus;
use App\Models\BusinessDay;
use App\Models\Sale;
use Illuminate\Support\Carbon;

abstract class BaseReport
{
    protected function businessDayQuery(
        ?Carbon $start = null,
        ?Carbon $end = null,
        ?int $locationId = null
    ) {
        return BusinessDay::query()
            ->when($start, fn($q) => $q->whereDate('date', '>=', $start))
            ->when($end, fn($q) => $q->whereDate('date', '<=', $end))
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->where('status', BusinessDayStatus::CLOSED);
    }

    protected function salesBaseQuery(
        ?Carbon $start = null,
        ?Carbon $end = null,
        ?int $locationId = null
    ) {
        return Sale::query()
            ->join('business_days', 'business_days.id', '=', 'sales.business_day_id')
            ->when($start, fn($q) => $q->whereDate('business_days.date', '>=', $start))
            ->when($end, fn($q) => $q->whereDate('business_days.date', '<=', $end))
            ->when($locationId, fn($q) => $q->where('business_days.location_id', $locationId))
            ->where('sales.status', SaleStatus::SETTLED);
    }
}
