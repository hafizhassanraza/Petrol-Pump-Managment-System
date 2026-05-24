<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'employee_code',
        'name',
        'cnic',
        'phone',
        'salary',
        'joining_date',
        'status',
    ];

    public function shifts()
    {
        return $this->hasMany(EmployeeShift::class);
    }
}