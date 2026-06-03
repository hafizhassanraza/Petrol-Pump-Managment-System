<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeAttendance extends Model
{
    public const STATUSES = ['present', 'absent', 'late', 'half_day', 'on_leave'];

    protected $fillable = [
        'employee_id',
        'attendance_date',
        'check_in',
        'check_out',
        'status',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'present' => 'Present',
            'absent' => 'Absent',
            'late' => 'Late',
            'half_day' => 'Half Day',
            'on_leave' => 'On Leave',
            default => ucfirst($this->status),
        };
    }

    public function getWorkedHoursAttribute(): ?float
    {
        if (! $this->check_in || ! $this->check_out) {
            return null;
        }

        $in = \Carbon\Carbon::parse($this->check_in);
        $out = \Carbon\Carbon::parse($this->check_out);

        if ($out->lessThanOrEqualTo($in)) {
            return null;
        }

        return round($out->diffInMinutes($in) / 60, 2);
    }
}
