<?php

namespace Tests\Concerns;

use App\Models\Sale;

trait CalculateSale
{
    protected function recalculateTotals(Sale $sale): void
    {
        $subtotal = $sale->items()->sum('total_price');

        $sale->subtotal = $subtotal;
        $sale->tax = 0; // adjust if needed
        $sale->discount = 0;
        $sale->total = $subtotal + $sale->tax - $sale->discount;

        $sale->save();
    }
}
