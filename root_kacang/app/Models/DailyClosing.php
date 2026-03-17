<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyClosing extends Model
{
    protected $fillable = [
        'business_day_id',
        'expected_cash',
        'received_cash',
        'difference'
    ];
}
