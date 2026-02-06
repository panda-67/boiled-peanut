<?php

namespace App\Services;

use App\Models\DailyClosing;
use App\Models\Sale;
use App\Models\Settlement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DailyClosingService
{
    public function close(Carbon $date): DailyClosing
    {
        if (Sale::whereDate('sale_date', $date)
            ->where('status', 'confirmed')
            ->exists()
        ) {
            throw new \Exception('Unsettled sales exist');
        }

        return DB::transaction(function () use ($date) {

            if (DailyClosing::where('date', $date)->exists()) {
                throw new \Exception('Day already closed');
            }

            $expected = Sale::whereDate('sale_date', $date)
                ->where('status', 'settled')
                ->sum('total');

            $received = Settlement::whereDate('received_at', $date)
                ->sum('amount_received');

            $difference = $received - $expected;

            return DailyClosing::create([
                'date' => $date,
                'expected_cash' => $expected,
                'received_cash' => $received,
                'difference' => $difference,
                'status' => 'closed',
                'closed_at' => now(),
            ]);
        });
    }
}
