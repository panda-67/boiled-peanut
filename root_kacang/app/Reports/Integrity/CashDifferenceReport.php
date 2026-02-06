<?php

namespace App\Reports\Integrity;

use App\Models\CashDifference;

class CashDifferenceReport
{
    public function history()
    {
        return CashDifference::orderByDesc('date')->get();
    }
}
