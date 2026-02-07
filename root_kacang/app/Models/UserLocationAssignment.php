<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLocationAssignment extends Model
{
    protected $fillable = [
        'user_id',
        'location_id',
        'active_from',
        'active_to',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
