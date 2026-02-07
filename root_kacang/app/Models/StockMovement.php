<?php

namespace App\Models;

use App\Domain\Inventory\ReferenceType;
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
