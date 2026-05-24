<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dispenser extends Model
{
    protected $fillable = [
        'dispenser_code',
        'company',
        'model',
        'status',
    ];

    public function nozzles()
    {
        return $this->hasMany(Nozzle::class);
    }
}