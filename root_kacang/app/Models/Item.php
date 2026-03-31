<?php

namespace App\Models;

use App\Enums\ItemType;
use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'unit',
        'is_stocked',
        'is_sellable',
        'is_purchasable',
        'default_unit_cost',
        'default_price',
        'min_stock',
        'is_active',
    ];

    protected $casts = [
        'type' => ItemType::class,
        'is_stocked' => 'boolean',
        'is_sellable' => 'boolean',
        'is_purchasable' => 'boolean',
        'is_active' => 'boolean',
        'default_unit_cost' => 'decimal:2',
        'default_price' => 'decimal:2',
        'min_stock' => 'decimal:2',
    ];

    public function productions(): BelongsToMany
    {
        return $this->belongsToMany(Production::class, 'production_materials')
            ->withPivot([
                'production_id',
                'quantity_used',
                'unit_cost',
                'total_cost',
            ])
            ->withTimestamps();
    }


    public function latestProduction(): HasOne
    {
        return $this->hasOne(Production::class)
            ->where('status', 'completed')
            ->latestOfMany('date');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stock(): float
    {
        return (float) $this->stockMovements()
            ->whereIn('type', [
                StockMovementType::IN,
                StockMovementType::OUT,
            ])
            ->sum('quantity');
    }

    public function stockAt(Location $location): float
    {
        return (float) $this->stockMovements()
            ->where('location_id', $location->id)
            ->whereIn('type', [
                StockMovementType::IN,
                StockMovementType::OUT,
            ])
            ->sum('quantity');
    }

    public function reservedAt(Location $location): float
    {
        return $this->transactions()
            ->where('location_id', $location->id)
            ->where('type', StockMovementType::RESERVE)
            ->sum('quantity');
    }

    public function availableAt(Location $location): float
    {
        return $this->stockAt($location) - $this->reservedAt($location);
    }
}
