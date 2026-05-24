<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tank extends Model
{
    protected $fillable = [
        'product_id',
        'tank_number',
        'capacity_liters',
        'current_stock_liters',
        'minimum_level',
        'status',
    ];


    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function product()
    {
        return $this->belongsTo(Product::class);
    }


    public function nozzles()
    {
        return $this->hasMany(Nozzle::class);
    }


    public function dipReadings()
    {
        return $this->hasMany(TankDipReading::class);
    }
}