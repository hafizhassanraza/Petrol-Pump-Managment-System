<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TankDipReading extends Model
{
    protected $fillable = [
        'tank_id',
        'reading_datetime',
        'measured_liters',
        'system_stock_liters',
        'difference_liters',
        'stock_reconciled',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'reading_datetime' => 'datetime',
            'measured_liters' => 'decimal:2',
            'system_stock_liters' => 'decimal:2',
            'difference_liters' => 'decimal:2',
            'stock_reconciled' => 'boolean',
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


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}