<?php

namespace App\Services;

use App\Enums\ReferenceType;
use App\Enums\StockMovementType;
use App\Models\Location;
use App\Models\Material;
use App\Models\StockMovement;

class StockMovementService
{
    public function inFromPurchase(
        Material $material,
        Location $location,
        float $qty,
        ?string $note = null
    ): void {
        StockMovement::create([
            'material_id'    => $material->id,
            'location_id'    => $location->id,
            'quantity'       => $qty, // POSITIF
            'type'           => StockMovementType::IN,
            'reference_type' => ReferenceType::PURCHASE,
            'note'           => $note,
        ]);
    }

    public function outForProduction(
        Material $material,
        Location $location,
        float $qty,
        string $productionId
    ): StockMovement {
        if ($material->stock() < $qty) {
            throw new \Exception('Stok material tidak mencukupi');
        }

        return StockMovement::create([
            'material_id'    => $material->id,
            'location_id'    => $location->id,
            'quantity'       => -abs($qty),
            'type'           => StockMovementType::OUT,
            'reference_type' => ReferenceType::PRODUCTION,
            'reference_id'   => $productionId,
            'note'           => 'Material used for production',
        ]);
    }
}
