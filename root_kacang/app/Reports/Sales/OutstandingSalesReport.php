<?php

namespace App\Reports\Sales;

use App\Models\Sale;

class OutstandingSalesReport
{
    public function all()
    {
        return Sale::query()
            ->where('payment_status', 'unpaid')
            ->where('status', 'confirmed')
            ->select([
                'invoice_number',
                'sale_date',
                'total'
            ])
            ->get();
    }
}
