<?php

namespace App\Services;

use App\Enums\ProductTransactionType;
use App\Enums\ReferenceType;
use App\Models\Location;
use App\Models\Material;
use App\Models\Production;
use App\Models\StockMovement;

class StockMovementService
{
    public function inForPurchase(
        Material $material,
        float $qty,
        ?string $note = null
    ): void {
        StockMovement::create([
            'material_id'    => $material->id,
            'location_id'    => $this->centralLocation()->id,
            'quantity'       => $qty, // POSITIF
            'type'           => ProductTransactionType::IN,
            'reference_type' => ReferenceType::PURCHASE,
            'note'           => $note,
        ]);
    }

    public function outForProduction(
        Material $material,
        float $qty,
        Production $production
    ): StockMovement {
        if ($material->stock() < $qty) {
            throw new \Exception('Stok material tidak mencukupi');
        }

        return StockMovement::create([
            'material_id'    => $material->id,
            'location_id'    => $this->centralLocation()->id,
            'quantity'       => -abs($qty),
            'type'           => ProductTransactionType::OUT,
            'reference_type' => ReferenceType::PRODUCTION,
            'reference_id'   => $production->id,
            'note'           => 'Material used for production',
        ]);
    }

    private function centralLocation(): Location
    {
        return Location::where('type', 'central')->firstOrFail();
    }
}
