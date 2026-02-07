<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'is_active'];

    public function productTransactions()
    {
        return $this->hasMany(ProductTransaction::class);
    }
}
