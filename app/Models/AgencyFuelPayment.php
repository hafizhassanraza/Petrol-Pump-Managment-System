<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyFuelPayment extends Model
{
    protected $fillable = [
        'agency_fuel_credit_id',
        'amount',
        'payment_method',
        'payment_date',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function credit(): BelongsTo
    {
        return $this->belongsTo(AgencyFuelCredit::class, 'agency_fuel_credit_id');
    }
}
