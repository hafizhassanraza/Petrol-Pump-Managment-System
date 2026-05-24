<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeShift extends Model
{
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
