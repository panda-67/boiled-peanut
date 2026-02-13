<?php

namespace App\Models;

use App\Policies\BusinessDayPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UsePolicy(BusinessDayPolicy::class)]
class BusinessDay extends Model
{
    /** @use HasFactory<\Database\Factories\BusinessDayFactory> */
    use HasFactory;

    protected $fillable = [
        'location_id',
        'date',
        'status',
        'opened_at',
        'opened_by',
        'closed_at',
        'closed_by',
    ];

    protected $casts = [
        'date' => 'date',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function productions(): HasMany
    {
        return $this->hasMany(Production::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isBalanced(): bool
    {
        return !$this->sales()->where('status', 'draft')->exists()
            && !$this->productions()->where('status', 'draft')->exists();
    }

    public static function activeFor(int $locationId): ?self
    {
        return self::where('location_id', $locationId)
            ->where('status', 'open')
            ->first();
    }
}
