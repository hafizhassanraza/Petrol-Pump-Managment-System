<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobilOilPurchase extends Model
{
    protected $fillable = [
        'mobil_oil_product_id',
        'invoice_no',
        'quantity',
        'purchase_rate',
        'total_amount',
        'received_datetime',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'purchase_rate' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'received_datetime' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(MobilOilProduct::class, 'mobil_oil_product_id');
    }
}
