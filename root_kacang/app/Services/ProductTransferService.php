<?php

namespace App\Services;

use App\Enums\ReferenceType;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductTransferService
{
    public function transfer(
        Product $product,
        Location $from,
        Location $to,
        float $qty,
        ?string $note = null
    ): void {
        if ($from->id === $to->id) {
            throw new \InvalidArgumentException('Source and destination cannot be the same');
        }

        if ($product->stockAt($from) < $qty) {
            throw new \Exception('Insufficient stock at source location');
        }

        DB::transaction(function () use ($product, $from, $to, $qty, $note) {
            app(ProductStockService::class)->stockOut(
                $product,
                $from,
                $qty,
                ReferenceType::TRANSFER,
                0,
                $note ?? 'Transfer out'
            );

            app(ProductStockService::class)->stockIn(
                $product,
                $to,
                $qty,
                ReferenceType::TRANSFER,
                0,
                $note ?? 'Transfer in'
            );
        });
    }
}
