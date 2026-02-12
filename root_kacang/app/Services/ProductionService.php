<?php

namespace App\Services;

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
                product: $production->product,
                location: $central,
                qty: $production->output_quantity,
                referenceType: ReferenceType::PRODUCTION,
                referenceId: $production->id,
                note: 'Production output'
            );

            $production->update([
                'total_cost' => $totalCost,
                'status'     => 'completed',
            ]);

            return $production;
        });
    }
}
