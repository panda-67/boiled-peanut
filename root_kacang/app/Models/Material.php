<?php

namespace App\Models;

use App\Policies\MaterialPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UsePolicy(MaterialPolicy::class)]
class Material extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'unit', 'is_stocked', 'default_unit_cost'];

    public function productions(): HasMany
    {
        return $this->hasMany(Production::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stock(): float
    {
        return (float) $this->stockMovements()->sum('quantity');
    }

    public function stockAt(Location $location): float
    {
        return (float) $this->stockMovements()
            ->where('location_id', $location->id)
            ->sum('quantity');
    }
}
