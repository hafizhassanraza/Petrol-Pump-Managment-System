<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OwnerFuelUsage extends Model
{
    protected $fillable = [

        'product_id',

        'nozzle_id',

        'employee_id',

        'vehicle_no',

        'person_name',

        'purpose',

        'liters',

        'price_per_liter',

        'total_amount',

        'usage_datetime',

        'notes',

        'created_by',
    ];


    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function product()
    {
        return $this->belongsTo(Product::class);
    }


    public function nozzle()
    {
        return $this->belongsTo(Nozzle::class);
    }


    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}