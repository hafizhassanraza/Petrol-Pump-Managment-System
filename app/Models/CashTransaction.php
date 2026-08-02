<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashTransaction extends Model
{
    public const TYPE_IN = 'cash_in';

    public const TYPE_OUT = 'cash_out';

    public const TYPES = [
        self::TYPE_IN,
        self::TYPE_OUT,
    ];

    public const PAYMENT_METHODS = ['cash', 'online'];

    protected $fillable = [
        'type',
        'category',
        'amount',
        'transaction_date',
        'payment_method',
        'reference_no',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isCashIn(): bool
    {
        return $this->type === self::TYPE_IN;
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === self::TYPE_IN ? 'Cash In' : 'Cash Out';
    }
}
