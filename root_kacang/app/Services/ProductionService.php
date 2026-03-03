<?php

namespace App\Services;

use App\Enums\ReferenceType;
use App\Models\Location;
use App\Models\Material;
use App\Models\Product;
use App\Models\Production;
use App\Services\Context\ActiveContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductionService
{
    public function draft(
        Product $product,
        int $qty,
        Collection $materials,
        ActiveContext $context
    ): Production {
        /** @var \App\Models\Production $production */
        $production = Production::create([
            'business_day_id' => $context->businessDay->id,
            'product_id' => $product->id,
            'output_quantity' => $qty,
            'date' => now()

        ]);

        $materialIds = $materials->pluck('material_id');
        $materialModels = Material::whereIn('id', $materialIds)->get()->keyBy('id');

        $data = $materials->mapWithKeys(function ($m) use ($materialModels) {
            $material = $materialModels[$m['material_id']];

            return [$material->id => [
                'quantity_used' => $m['quantity_used'],
                'unit_cost'     => $material->default_unit_cost,
                'total_cost'    => $material->default_unit_cost * $m['quantity_used'],
            ]];
        });

        $production->materials()->syncWithoutDetaching($data);

        return $production->fresh(['materials']);
    }

    public function execute(Production $production): Production
    {
        if ($production->status !== 'draft') {
            throw new \Exception('Production already processed');
        }

        return DB::transaction(function () use ($production) {

            $production->load(['materials', 'businessDay.location'])->lockForUpdate();

            $central = Location::where('id', $production->businessDay->location->id)
                ->where('type', 'central')
                ->firstOrFail();

            $totalCost = 0;

            foreach ($production->materials as $material) {
                $qty = $material->pivot->quantity_used;

                // Material OUT
                if ($material->is_stocked) {
                    app(StockMovementService::class)
                        ->outForProduction($material, $central, $qty, $production);
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
