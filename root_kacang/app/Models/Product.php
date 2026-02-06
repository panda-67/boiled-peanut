<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'selling_price'];

    public function productions(): HasMany
    {
        return $this->hasMany(Production::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(ProductTransaction::class);
    }

    public function stock(): float
    {
        return (float) $this->transactions()->sum('quantity');
    }
}
