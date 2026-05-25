<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'unit',
        'status',
    ];
    public function prices()
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function tanks()
    {
        return $this->hasMany(Tank::class);
    }

    public function nozzles()
    {
        return $this->hasMany(Nozzle::class);
    }

    public function latestPrice()
    {
        return $this->hasOne(ProductPrice::class)->latestOfMany('effective_from');
    }
}
