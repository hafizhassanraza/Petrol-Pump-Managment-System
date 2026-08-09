<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgencyCustomer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'cnic',
        'address',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function credits(): HasMany
    {
        return $this->hasMany(AgencyFuelCredit::class);
    }

    public function outstandingBalance(): float
    {
        return (float) $this->credits()
            ->whereIn('status', ['open', 'partial'])
            ->get()
            ->sum(fn (AgencyFuelCredit $c) => $c->balance());
    }
}
