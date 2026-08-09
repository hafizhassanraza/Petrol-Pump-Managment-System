<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeShift extends Model
{
    protected $fillable = [
        'employee_id',
        'nozzle_id',
        'shift_id',
        'assigned_date',
        'closed_date',
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

    protected function casts(): array
    {
        return [
            'assigned_date' => 'date',
            'closed_date' => 'date',
            'opening_reading' => 'decimal:2',
            'closing_reading' => 'decimal:2',
            'testing_liters' => 'decimal:2',
            'total_liters' => 'decimal:2',
            'price_per_liter' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'cash_received' => 'decimal:2',
            'online_received' => 'decimal:2',
            'shortage_amount' => 'decimal:2',
            'extra_amount' => 'decimal:2',
            'submitted_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function nozzle(): BelongsTo
    {
        return $this->belongsTo(Nozzle::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function ownerFuelUsage(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(OwnerFuelUsage::class);
    }

    public function agencyFuelCredit(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AgencyFuelCredit::class);
    }

    /**
     * Sales / report date is the closing date (when the shift was closed).
     */
    public function scopeClosedBetween($query, string $from, string $to)
    {
        return $query->whereNotNull('closed_date')
            ->whereBetween('closed_date', [$from, $to]);
    }

    public function scopeClosedOn($query, string $date)
    {
        return $query->whereDate('closed_date', $date);
    }

    public function scopeClosedOnOrBefore($query, string $date)
    {
        return $query->whereNotNull('closed_date')
            ->where('closed_date', '<=', $date);
    }

    public function scopeClosedAfter($query, string $date)
    {
        return $query->whereNotNull('closed_date')
            ->where('closed_date', '>', $date);
    }
}
