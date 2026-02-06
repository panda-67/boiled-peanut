<?php

namespace App\Services;

use App\Domain\Inventory\ReferenceType;
use App\Models\Production;
use Illuminate\Support\Facades\DB;

class ProductionService
{
    public function execute(Production $production): Production
    {
        if ($production->status !== 'draft') {
            throw new \Exception('Production already processed');
        }

        return DB::transaction(function () use ($production) {

            $totalCost = 0;

            foreach ($production->materials as $material) {
                $qty = $material->pivot->quantity_used;

                // Material OUT
                if ($material->is_stocked) {
                    app(StockMovementService::class)
                        ->outForProduction($material, $qty, $production);
                }

                $totalCost += $material->pivot->total_cost;
            }

            // Product IN
            app(ProductStockService::class)->stockIn(
                $production->product,
                $production->output_quantity,
                ReferenceType::PRODUCTION,
                $production->id,
                'Production output'
            );

            $production->update([
                'total_cost' => $totalCost,
                'status'     => 'completed',
            ]);

            return $production;
        });
    }
}
