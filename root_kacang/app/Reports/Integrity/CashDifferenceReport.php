<?php

namespace App\Reports\Integrity;

use App\Models\DailyClosing;

class CashDifferenceReport
{
    public function history()
    {
        return DailyClosing::query()
            ->with('businessDay')
            ->orderByDesc('created_at')
            ->get([
                'business_day_id',
                'expected_cash',
                'received_cash',
                'difference'
            ]);
    }
}
