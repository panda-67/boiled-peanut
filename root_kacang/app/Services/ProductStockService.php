<?php

namespace App\Services;

use App\Enums\ProductTransactionType;
use App\Enums\ReferenceType;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductTransaction;

use function Symfony\Component\Clock\now;

class ProductStockService
{
    public function stockIn(
        Product $product,
        Location $location,
        float $qty,
        ProductTransactionType $type,
        ReferenceType $referenceType,
        string $referenceId,
        ?string $note = null
    ): ProductTransaction {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }

        return ProductTransaction::create([
            'product_id'     => $product->id,
            'location_id'    => $location->id,
            'type'           => $type,
            'quantity'       => abs($qty),
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'note'           => $note,
            'date'           => now(),
        ]);
    }

    public function reserve(
        Product $product,
        Location $location,
        int $qty,
        ProductTransactionType $type,
        ReferenceType $referenceType,
        string $referenceId,
        ?string $note = null
    ): void {
        ProductTransaction::create([
            'product_id'     => $product->id,
            'location_id'    => $location->id,
            'type'           => $type,
            'quantity'       => -$qty, // negatif = terikat
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
        ProductTransactionType $type,
        ReferenceType $referenceType,
        string $referenceId,
        ?string $note = null
    ): ProductTransaction {
        if ($product->stockAt($location) < $qty) {
            throw new \Exception("Stok produk tidak mencukupi di lokasi {$location->name}");
        }
        return ProductTransaction::create([
            'product_id'     => $product->id,
            'location_id'    => $location->id,
            'type'           => $type,
            'quantity'       => -abs($qty),
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'note'           => $note,
            'date'           => now(),
        ]);
    }
}
