<?php

namespace App\Reports\Stocks;

use App\Enums\ReferenceType;
use App\Models\StockMovement;
use Illuminate\Support\Carbon;

class DailyMaterialUsageReport
{
    public function forDate(Carbon $start, Carbon $end)
    {
        return StockMovement::query()
            ->join('productions', 'stock_movements.reference_id', '=', 'productions.id')
            ->whereBetween('productions.date', [$start->startOfDay(), $end->endOfDay()])
            ->where('stock_movements.quantity', '<', 0)
            ->where('stock_movements.reference_type', ReferenceType::PRODUCTION)
            ->selectRaw('stock_movements.material_id, ABS(SUM(stock_movements.quantity)) as used')
            ->groupBy('stock_movements.material_id')
            ->join('materials', 'materials.id', '=', 'stock_movements.material_id')
            ->selectRaw('materials.id, materials.name, materials.unit, ABS(SUM(stock_movements.quantity)) as used')
            ->groupBy('materials.id', 'materials.name', 'materials.unit')
            ->get();
    }
}
