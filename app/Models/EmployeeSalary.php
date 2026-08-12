<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalary extends Model
{
    public const TYPE_FULL = 'full';
    public const TYPE_ADVANCE = 'advance';
    public const TYPE_PARTIAL = 'partial';
    public const TYPE_BONUS = 'bonus';

    public const TYPES = [
        self::TYPE_FULL,
        self::TYPE_ADVANCE,
        self::TYPE_PARTIAL,
        self::TYPE_BONUS,
    ];

    public const PAYMENT_METHODS = ['cash', 'bank', 'online'];

    protected $fillable = [
        'employee_id',
        'type',
        'amount',
        'payment_date',
        'salary_month',
        'payment_method',
        'reference_no',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'salary_month' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function typeLabels(): array
    {
        return [
            self::TYPE_FULL => 'Full Salary',
            self::TYPE_ADVANCE => 'Advance',
            self::TYPE_PARTIAL => 'Partial Salary',
            self::TYPE_BONUS => 'Bonus',
        ];
    }

    public function typeLabel(): string
    {
        return self::typeLabels()[$this->type] ?? ucfirst($this->type);
    }

    public function isCash(): bool
    {
        return $this->payment_method === 'cash';
    }
}
