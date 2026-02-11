<?php

namespace App\Models;

use App\Enums\ReferenceType;
use App\Enums\SaleStatus;
use App\Policies\SalePolicy;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[UsePolicy(SalePolicy::class)]
class Sale extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'invoice_number',
        'sale_date',
        'subtotal',
        'discount',
        'tax',
        'total',
        'payment_status',
        'payment_method',
        'confirmed_at',
        'paid_at',
        'user_id',
        'location_id',
        'business_day_id',
    ];

    protected $casts = [
        'status' => SaleStatus::class,
    ];

    protected array $guardIgnoredDirty = [
        'updated_at',
    ];

    protected static function booted()
    {
        static::updating(function (Sale $sale) {

            $from = $sale->getOriginal('status');
            $to   = $sale->status;

            $dirty = array_keys($sale->getDirty());
            $dirty = array_diff($dirty, $sale->guardIgnoredDirty);

            // 1. State transition
            if ($from !== $to) {

                $allowed = $sale->allowedDirtyForStateTransition($from, $to);

                if (empty($allowed)) {
                    throw new DomainException('SALE_INVALID_STATE_TRANSITION');
                }

                if (array_diff($dirty, $allowed)) {
                    throw new DomainException('SALE_IMMUTABLE_AFTER_CONFIRM');
                }

                return;
            }

            // 2. Non-state change
            if ($from !== SaleStatus::DRAFT) {
                throw new DomainException('SALE_IMMUTABLE_AFTER_CONFIRM');
            }
        });
    }

    public function confirm(): void
    {
        if ($this->status !== SaleStatus::DRAFT) {
            throw new DomainException('CONFIRM_SALE_INVALID_STATE');
        }

        $this->status = SaleStatus::CONFIRMED;
        $this->confirmed_at = now();
    }

    public function settle(): void
    {
        if ($this->status !== SaleStatus::CONFIRMED) {
            throw new DomainException('SETTLE_INVALID_STATE');
        }

        $this->status = SaleStatus::SETTLED;
        $this->paid_at = now();
    }

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

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function businessDay(): BelongsTo
    {
        return $this->belongsTo(BusinessDay::class);
    }

    protected function allowedDirtyForStateTransition(
        SaleStatus $from,
        SaleStatus $to
    ): array {
        return match (true) {
            $from === SaleStatus::DRAFT
                && $to === SaleStatus::CONFIRMED
            => ['status', 'confirmed_at'],

            $from === SaleStatus::CONFIRMED
                && $to === SaleStatus::SETTLED
            => ['status', 'paid_at'],

            default => [],
        };
    }
}
