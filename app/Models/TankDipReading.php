<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TankDipReading extends Model
{
    protected $fillable = [

        'tank_id',

        'reading_datetime',

        'measured_liters',

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


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}