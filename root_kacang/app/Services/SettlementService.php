<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Settlement;
use Illuminate\Support\Facades\DB;

class SettlementService
{
    public function settle(Sale $sale, float $amountReceived): Settlement
    {
        if ($sale->status !== 'confirmed') {
            throw new \Exception('Only confirmed sale can be settled');
        }

        if ($sale->settlement()->exists()) {
            throw new \Exception('Sale already settled');
        }

        if ($amountReceived <= 0) {
            throw new \Exception('Invalid settlement amount');
        }

        if ($amountReceived != $sale->total) {
            throw new \Exception('Settlement amount must match sale total');
        }

        return DB::transaction(function () use ($sale, $amountReceived) {

            $settlement = Settlement::create([
                'sale_id'         => $sale->id,
                'amount_received' => $amountReceived,
                'received_at'     => now(),
                'method'          => 'warung',
            ]);

            $sale->update([
                'status' => 'settled',
            ]);

            return $settlement;
        });
    }
}
