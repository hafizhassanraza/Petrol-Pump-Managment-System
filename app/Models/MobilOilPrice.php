<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobilOilPrice extends Model
{
    protected $fillable = [
        'mobil_oil_product_id',
        'price',
        'effective_from',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'effective_from' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(MobilOilProduct::class, 'mobil_oil_product_id');
    }
}
