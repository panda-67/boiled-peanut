<?php

namespace App\Services;

use App\Enums\ProductTransactionType;
use App\Enums\ReferenceType;
use App\Models\Location;
use App\Models\Production;
use Illuminate\Support\Facades\DB;

class ProductionService
{
    public function execute(Production $production): Production
    {
        if ($production->status !== 'draft') {
            throw new \Exception('Production already processed');
        }

        $central = Location::where('type', 'central')->firstOrFail();

        return DB::transaction(function () use ($production, $central) {

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
                $central,
                $production->output_quantity,
                ProductTransactionType::IN,
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
