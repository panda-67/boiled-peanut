<?php

namespace App\Models;

use App\Domain\Inventory\ReferenceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'sale_date',
        'subtotal',
        'discount',
        'tax',
        'total',
        'status',
        'payment_status',
        'payment_method',
        'paid_at',
        'user_id',
        'location_id',
    ];

    public function productTransactions(): HasMany
    {
        return $this->hasMany(ProductTransaction::class, 'reference_id')
            ->where('reference_type', ReferenceType::SALE);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function settlement(): HasOne
    {
        return $this->hasOne(Settlement::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    protected static function booted()
    {
        static::updating(function ($sale) {
            if (
                $sale->isDirty('status') &&
                $sale->getOriginal('status') === 'settled'
            ) {
                throw new \Exception('Settled sale cannot be modified');
            }
        });
    }
}
