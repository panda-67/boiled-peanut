<?php

namespace App\Services;

use App\Domain\Inventory\ReferenceType;
use App\Models\Sale;
use App\Models\ProductTransaction;
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

                // 1. Validate derived stock
                if ($product->stock() < $qty) {
                    throw new \Exception(
                        "Insufficient stock for {$product->name}"
                    );
                }

                // 2. Lock line total
                $lineTotal = $qty * $item->unit_price;

                $item->update([
                    'total_price' => $lineTotal,
                ]);

                // 3. Ledger write (ONLY stock mutation)
                ProductTransaction::create([
                    'product_id'     => $product->id,
                    'type'           => 'out',
                    'quantity'       => -abs($qty),
                    'reference_type' => ReferenceType::SALE,
                    'reference_id'   => $sale->id,
                    'date'           => now(),
                ]);

                $subtotal += $lineTotal;
            }

            // 4. Finalize sale
            $sale->update([
                'subtotal' => $subtotal,
                'total'    => $subtotal - $sale->discount + $sale->tax,
                'status'   => 'confirmed',
            ]);

            return $sale;
        });
    }
}
