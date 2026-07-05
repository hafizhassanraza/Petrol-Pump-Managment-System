<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TankRefill extends Model
{
    protected $fillable = [

        'tank_id',

        'product_id',

        'invoice_no',

        'quantity_liters',

        'stock_before_liters',

        'purchase_rate',

        'total_amount',

        'received_datetime',

        'notes',

        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity_liters' => 'decimal:2',
            'stock_before_liters' => 'decimal:2',
            'purchase_rate' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'received_datetime' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function tank()
    {
        return $this->belongsTo(Tank::class);
    }


    public function product()
    {
        return $this->belongsTo(Product::class);
    }


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}