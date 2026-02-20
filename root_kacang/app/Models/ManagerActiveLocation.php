<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManagerActiveLocation extends Model
{
    protected $fillable = ['user_id', 'location_id'];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
