<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyClosing extends Model
{
    protected $fillable = ['date', 'expected_cash', 'received_cash', 'difference', 'status', 'closed_at'];
}
