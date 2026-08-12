<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'module',
        'description',
        'method',
        'url',
        'route_name',
        'ip_address',
        'user_agent',
        'properties',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function actionLabels(): array
    {
        return [
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'closed' => 'Closed',
            'verified' => 'Verified',
            'paid' => 'Paid',
            'exported' => 'Exported',
            'viewed' => 'Viewed',
            'logged_in' => 'Logged In',
            'logged_out' => 'Logged Out',
            'other' => 'Other',
        ];
    }

    public static function moduleLabel(?string $module): string
    {
        if ($module === null || $module === '') {
            return 'System';
        }

        $labels = [
            'auth' => 'Authentication',
            'dashboard' => 'Dashboard',
            'reports' => 'Reports',
            'employees' => 'Employees',
            'employee-salaries' => 'Employee Salaries',
            'employee-attendances' => 'Attendance',
            'employee-shifts' => 'Employee Shifts',
            'expenses' => 'Expenses',
            'cash-transactions' => 'Cash In / Out',
            'agency-customers' => 'Agency Customers',
            'owner-fuel-usages' => 'Owner Fuel',
            'tank-refills' => 'Tank Refills',
            'tank-dip-readings' => 'Tank Dip Readings',
            'product-prices' => 'Fuel Prices',
            'tanks' => 'Tanks',
            'dispensers' => 'Dispensers',
            'nozzles' => 'Nozzles',
            'mobil-oil.products' => 'Mobil Oil Products',
            'mobil-oil.purchases' => 'Mobil Oil Purchases',
            'mobil-oil.sales' => 'Mobil Oil Sales',
            'profile' => 'Profile',
        ];

        return $labels[$module] ?? \Illuminate\Support\Str::headline(str_replace(['-', '.'], ' ', $module));
    }

    public function actionLabel(): string
    {
        return self::actionLabels()[$this->action] ?? ucfirst(str_replace('_', ' ', $this->action));
    }

    public function moduleDisplay(): string
    {
        return self::moduleLabel($this->module);
    }
}
