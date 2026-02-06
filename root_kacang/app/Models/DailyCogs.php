<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyCogs extends Model
{
    protected $fillable = ['date', 'quantity_sold', 'average_cost', 'cogs_amount'];
}
