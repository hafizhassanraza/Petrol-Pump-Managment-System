<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobilOilSale extends Model
{
    public const PAYMENT_METHODS = ['cash', 'online'];

    protected $fillable = [
        'mobil_oil_product_id',
        'employee_id',
        'quantity',
        'unit_price',
        'total_amount',
        'payment_method',
        'sold_datetime',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'sold_datetime' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(MobilOilProduct::class, 'mobil_oil_product_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
