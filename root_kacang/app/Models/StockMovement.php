<?php

namespace App\Models;

use App\Enums\ReferenceType;
use App\Enums\StockMovementType;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'item_id',
        'location_id',
        'type',
        'quantity',
        'reference_type',
        'reference_id',
        'note',
        'date'
    ];

    protected $casts = [
        'date' => 'datetime',
        'quantity' => 'decimal:3',
        'type' => StockMovementType::class,
        'reference_type' => ReferenceType::class,
    ];

    protected static function booted()
    {
        static::creating(function ($transaction) {

            // Enforce signed rule
            if (
                $transaction->type === StockMovementType::OUT &&
                $transaction->quantity > 0
            ) {
                throw new DomainException('OUT_MUST_BE_NEGATIVE');
            }

            if (
                $transaction->type === StockMovementType::IN &&
                $transaction->quantity < 0
            ) {
                throw new DomainException('IN_MUST_BE_POSITIVE');
            }

            if (
                $transaction->type === StockMovementType::RESERVE &&
                $transaction->quantity === 0
            ) {
                throw new DomainException('INVALID_RESERVE_QUANTITY');
            }
        });
    }


    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function reference()
    {
        return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
