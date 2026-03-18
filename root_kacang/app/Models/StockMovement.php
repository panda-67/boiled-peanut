<?php

namespace App\Models;

use App\Enums\ReferenceType;
use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'material_id',
        'location_id',
        'quantity',
        'type',
        'reference_type',
        'reference_id',
        'note'
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'type' => StockMovementType::class,
        'reference_type' => ReferenceType::class
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function production()
    {
        return $this->belongsTo(Production::class, 'reference_id')
            ->where('reference_type', ReferenceType::PRODUCTION);
    }
}
