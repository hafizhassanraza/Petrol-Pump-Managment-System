<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgencyFuelCredit extends Model
{
    protected $fillable = [
        'agency_customer_id',
        'employee_shift_id',
        'nozzle_id',
        'product_id',
        'employee_id',
        'liters',
        'price_per_liter',
        'total_amount',
        'paid_amount',
        'status',
        'credit_datetime',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'liters' => 'decimal:2',
            'price_per_liter' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'credit_datetime' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(AgencyCustomer::class, 'agency_customer_id');
    }

    public function employeeShift(): BelongsTo
    {
        return $this->belongsTo(EmployeeShift::class);
    }

    public function nozzle(): BelongsTo
    {
        return $this->belongsTo(Nozzle::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(AgencyFuelPayment::class);
    }

    public function balance(): float
    {
        return round((float) $this->total_amount - (float) $this->paid_amount, 2);
    }

    public function refreshPaymentStatus(): void
    {
        $paid = round((float) $this->payments()->sum('amount'), 2);
        $total = (float) $this->total_amount;

        if ($paid <= 0) {
            $status = 'open';
        } elseif ($paid + 0.009 >= $total) {
            $status = 'paid';
            $paid = $total;
        } else {
            $status = 'partial';
        }

        $this->update([
            'paid_amount' => $paid,
            'status' => $status,
        ]);
    }
}
