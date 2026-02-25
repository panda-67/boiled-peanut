<?php

namespace App\Models;

use App\Enums\LocationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Location extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'fee', 'is_active'];

    protected $casts = [
        'type' => LocationType::class,
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->_id = Str::uuid7(now());
        });
    }

    public function productTransactions()
    {
        return $this->hasMany(ProductTransaction::class);
    }

    public function openBusinessDay(): HasOne
    {
        return $this->hasOne(BusinessDay::class)
            ->where('status', 'open')
            ->latestOfMany();
    }
}
