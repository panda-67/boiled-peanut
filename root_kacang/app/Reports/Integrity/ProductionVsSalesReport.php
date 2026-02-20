<?php

namespace App\Reports\Integrity;

use App\Models\ProductTransaction;

class ProductionVsSalesReport
{
    public function summary()
    {
        $produced = ProductTransaction::where('type', 'in')->sum('quantity');
        $sold     = abs(ProductTransaction::where('type', 'out')->sum('quantity'));

        return [
            'produced' => $produced,
            'sold'     => $sold,
            'balance'  => $produced - $sold,
        ];
    }
}
