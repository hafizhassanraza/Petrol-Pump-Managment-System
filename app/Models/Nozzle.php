<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nozzle extends Model
{
    protected $fillable = [
        'dispenser_id',
        'tank_id',
        'product_id',
        'nozzle_number',
        'current_meter_reading',
        'status',
    ];

    public function dispenser()
    {
        return $this->belongsTo(Dispenser::class);
    }

    public function tank()
    {
        return $this->belongsTo(Tank::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}