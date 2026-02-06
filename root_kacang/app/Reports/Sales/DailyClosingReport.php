<?php

namespace App\Reports\Sales;

use App\Models\DailyClosing;
use Illuminate\Support\Carbon;

class DailyClosingReport
{
    public function forDate(Carbon $date)
    {
        return DailyClosing::where('date', $date)->first();
    }

    public function history()
    {
        return DailyClosing::orderByDesc('date')->get();
    }
}
