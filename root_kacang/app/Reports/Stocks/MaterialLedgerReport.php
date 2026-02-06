<?php

namespace App\Reports\Stocks;

use App\Models\StockMovement;

class MaterialLedgerReport
{
    public function forMaterial(int $materialId)
    {
        return StockMovement::query()
            ->where('material_id', $materialId)
            ->orderBy('date')
            ->get([
                'date',
                'quantity',
                'reference_type',
                'reference_id',
            ]);
    }
}
