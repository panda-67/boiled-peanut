<?php

namespace App\Models;

use App\Domain\Inventory\ReferenceType;
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
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'reference_id')
            ->where('reference_type', ReferenceType::SALE);
    }

    public function production()
    {
        return $this->belongsTo(Production::class, 'reference_id')
            ->where('reference_type', ReferenceType::PRODUCTION);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
