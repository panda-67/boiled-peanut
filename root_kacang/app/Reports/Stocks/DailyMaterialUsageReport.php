<?php

namespace App\Reports\Stocks;

use App\Domain\Inventory\ReferenceType;
use App\Models\StockMovement;
use Illuminate\Support\Carbon;

class DailyMaterialUsageReport
{
    public function forDate(Carbon $date)
    {
        return StockMovement::query()
            ->whereDate('date', $date)
            ->where('quantity', '<', 0)
            ->where('reference_type', ReferenceType::PRODUCTION)
            ->selectRaw('material_id, ABS(SUM(quantity)) as used')
            ->groupBy('material_id')
            ->with('material:id,name,unit')
            ->get();
    }
}
