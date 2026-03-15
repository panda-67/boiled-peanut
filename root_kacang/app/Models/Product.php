<?php

namespace App\Models;

use App\Enums\ProductTransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'selling_price', 'unit'];

    public function productions(): HasMany
    {
        return $this->hasMany(Production::class);
    }

    public function latestProduction(): HasOne
    {
        return $this->hasOne(Production::class)
            ->where('status', 'completed')
            ->latestOfMany('date');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(ProductTransaction::class);
    }

    public function stock(): float
    {
        return (float) $this->transactions()
            ->whereIn('type', [
                ProductTransactionType::IN,
                ProductTransactionType::OUT,
            ])
            ->sum('quantity');
    }

    public function stockAt(Location $location): float
    {
        return (float) $this->transactions()
            ->where('location_id', $location->id)
            ->whereIn('type', [
                ProductTransactionType::IN,
                ProductTransactionType::OUT,
            ])
            ->sum('quantity');
    }

    public function reservedAt(Location $location): float
    {
        return $this->transactions()
            ->where('location_id', $location->id)
            ->where('type', ProductTransactionType::RESERVE)
            ->sum('quantity');
    }

    public function availableAt(Location $location): float
    {
        return $this->stockAt($location) - $this->reservedAt($location);
    }
}
