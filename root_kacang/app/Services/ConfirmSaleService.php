<?php

namespace App\Services;

use App\Domain\Inventory\ReferenceType;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class ConfirmSaleService
{
    public function confirm(Sale $sale): Sale
    {
        if ($sale->status !== 'draft') {
            throw new \Exception('Sale cannot be confirmed');
        }

        if ($sale->items->isEmpty()) {
            throw new \Exception('Sale has no items');
        }

        if ($sale->productTransactions()->exists()) {
            throw new \Exception('Sale already confirmed');
        }

        return DB::transaction(function () use ($sale) {

            $subtotal = 0;

            foreach ($sale->items as $item) {
                $product = $item->product;
                $qty = $item->quantity;

                // 1. Lock line total
                $lineTotal = $qty * $item->unit_price;

                $item->update([
                    'total_price' => $lineTotal,
                ]);

                // 2. Ledger write (ONLY stock mutation) Product OUT
                app(ProductStockService::class)->stockOut(
                    $product,
                    $sale->location,
                    $qty,
                    ReferenceType::SALE,
                    $sale->id,
                    'Product selling'
                );

                $subtotal += $lineTotal;
            }

            // 3. Finalize sale
            $sale->update([
                'subtotal' => $subtotal,
                'total'    => $subtotal - $sale->discount + $sale->tax,
                'status'   => 'confirmed',
            ]);

            return $sale;
        });
    }
}
