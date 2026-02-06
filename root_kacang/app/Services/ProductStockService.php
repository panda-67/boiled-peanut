<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductTransaction;

class ProductStockService
{
    public function stockIn(
        Product $product,
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
        float $qty,
        string $referenceType,
        int $referenceId,
        ?string $note = null
    ): ProductTransaction {
        if ($product->stock() < $qty) {
            throw new \Exception('Stok produk tidak mencukupi');
        }

        return ProductTransaction::create([
            'product_id'     => $product->id,
            'type'           => 'out',
            'quantity'       => -abs($qty),
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'note'           => $note,
            'date'           => now(),
        ]);
    }
}
