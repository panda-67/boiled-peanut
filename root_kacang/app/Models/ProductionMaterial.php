<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionMaterial extends Model
{
    protected $fillable = [
        'production_id',
        'material_id',
        'quantity_used',
        'unit_cost',
        'total_cost',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function production(): BelongsTo
    {
        return $this->belongsTo(Production::class);
    }
}
