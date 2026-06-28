<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MobilOilProduct extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'unit',
        'current_stock_qty',
        'minimum_level',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'current_stock_qty' => 'decimal:2',
            'minimum_level' => 'decimal:2',
            'status' => 'boolean',
        ];
    }

    public function prices(): HasMany
    {
        return $this->hasMany(MobilOilPrice::class);
    }

    public function latestPrice(): HasOne
    {
        return $this->hasOne(MobilOilPrice::class)->latestOfMany('effective_from');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(MobilOilPurchase::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(MobilOilSale::class);
    }
}
