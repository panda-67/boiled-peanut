<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Product;
use App\Models\ProductTransaction;

class ProductStockService
{
    public function stockIn(
        Product $product,
        Location $location,
        float $qty,
        string $referenceType,
        int $referenceId,
        ?string $note = null
    ): ProductTransaction {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }

        return ProductTransaction::create([
            'product_id'     => $product->id,
            'location_id'    => $location->id,
            'type'           => 'in',
            'quantity'       => abs($qty),
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'note'           => $note,
            'date'           => now(),
        ]);
    }

    public function stockOut(
        Product $product,
        Location $location,
        float $qty,
        string $referenceType,
        int $referenceId,
        ?string $note = null
    ): ProductTransaction {
        if ($product->stockAt($location) < $qty) {
            throw new \Exception("Stok produk tidak mencukupi di lokasi {$location->name}");
        }
        return ProductTransaction::create([
            'product_id'     => $product->id,
            'location_id'    => $location->id,
            'type'           => 'out',
            'quantity'       => -abs($qty),
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'note'           => $note,
            'date'           => now(),
        ]);
    }
}
