<?php

namespace App\Services;

use App\Enums\ReferenceType;
use App\Models\Item;
use App\Models\Location;
use App\Models\Production;
use App\Services\Context\ActiveContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductionService
{
    public function draft(
        Item $product,
        int $qty,
        Collection $materials,
        ActiveContext $context
    ): Production {
        /** @var \App\Models\Production $production */
        $production = Production::create([
            'business_day_id' => $context->businessDay->id,
            'item_id' => $product->id,
            'output_quantity' => $qty,
            'date' => now()

        ]);

        $materialIds = $materials->pluck('material_id');
        $materialModels = Item::whereIn('id', $materialIds)->get()->keyBy('id');

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

            $production->materials->each(function ($material) use (&$totalCost, $central) {
                $qty = $material->pivot->quantity_used;

                // Material OUT
                if ($material->is_stocked) {
                    app(StockMovementService::class)
                        ->outForProduction($material, $central, $qty, $material->pivot->production_id);
                }

                $totalCost += $material->pivot->total_cost;
            });

            $qty = $production->output_quantity;

            // Product IN
            app(ProductStockService::class)->stockIn(
                product: $production->item,
                location: $central,
                qty: $qty,
                referenceType: ReferenceType::PRODUCTION,
                referenceId: $production->id,
                note: 'Production output'
            );

            $unitCost = $qty > 0
                ? bcdiv((string) $totalCost, (string) $qty, 2)
                : '0.00';

            $production->update([
                'total_cost' => $totalCost,
                'unit_cost'  => $unitCost,
                'status'     => 'completed',
            ]);

            return $production;
        });
    }
}
