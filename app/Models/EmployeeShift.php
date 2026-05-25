<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeShift extends Model
{

    protected $fillable = [
        'employee_id',
        'nozzle_id',
        'shift_id',
        'assigned_date',
        'opening_reading',
        'closing_reading',
        'testing_liters',
        'total_liters',
        'price_per_liter',
        'total_amount',
        'cash_received',
        'online_received',
        'shortage_amount',
        'extra_amount',
        'submitted_at',
        'status',
        'verified_by',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function nozzle()
    {
        return $this->belongsTo(Nozzle::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
