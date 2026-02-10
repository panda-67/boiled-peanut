<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Settlement extends Model
{
    use HasUuids;

    protected $fillable = ['sale_id', 'amount_received', 'method', 'received_at', 'note'];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
