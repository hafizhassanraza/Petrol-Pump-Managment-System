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

        'purchase_rate',

        'total_amount',

        'received_datetime',

        'notes',

        'created_by',
    ];


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