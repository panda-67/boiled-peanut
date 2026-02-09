<?php

namespace App\Models;

use App\Enums\ReferenceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Production extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'product_id',
        'output_quantity',
        'total_cost',
        'status'
    ];

    public function materialMovements()
    {
        return $this->hasMany(StockMovement::class, 'reference_id')
            ->where('reference_type', ReferenceType::PRODUCTION);
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'production_materials')
            ->withPivot([
                'quantity_used',
                'unit_cost',
                'total_cost',
            ])
            ->withTimestamps();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
