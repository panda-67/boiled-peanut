<?php

namespace App\Models;

use App\Enums\ReferenceType;
use App\Enums\ProductTransactionType;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductTransaction extends Model
{
    protected $fillable = [
        'product_id',
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
        'type' => ProductTransactionType::class,
        'reference_type' => ReferenceType::class,
    ];

    protected static function booted()
    {
        static::creating(function ($transaction) {

            // Enforce signed rule
            if (
                $transaction->type === ProductTransactionType::OUT &&
                $transaction->quantity > 0
            ) {
                throw new DomainException('OUT_MUST_BE_NEGATIVE');
            }

            if (
                $transaction->type === ProductTransactionType::IN &&
                $transaction->quantity < 0
            ) {
                throw new DomainException('IN_MUST_BE_POSITIVE');
            }

            if (
                $transaction->type === ProductTransactionType::RESERVE &&
                $transaction->quantity === 0
            ) {
                throw new DomainException('INVALID_RESERVE_QUANTITY');
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'reference_id')
            ->where('reference_type', ReferenceType::SALE);
    }

    public function production(): BelongsTo
    {
        return $this->belongsTo(Production::class, 'reference_id')
            ->where('reference_type', ReferenceType::PRODUCTION);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
