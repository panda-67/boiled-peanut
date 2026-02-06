<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashDifference extends Model
{
    protected $fillable = ['date', 'expected_cash', 'received_cash', 'difference', 'note'];
}
