<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

class AuditLogger
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        '_token',
        '_method',
    ];

    private const DETAIL_KEYS = [
        'name',
        'employee_code',
        'type',
        'amount',
        'expense_type',
        'category',
        'payment_method',
        'payment_date',
        'expense_date',
        'salary_month',
        'tank_number',
        'dispenser_code',
        'nozzle_number',
        'product_id',
        'liters',
        'price',
        'phone',
        'reference_no',
        'notes',
        'status',
        'filter',
        'from',
        'to',
    ];

    public static function log(
        string $action,
        string $description,
        ?string $module = null,
        ?array $properties = null,
        ?Request $request = null,
        ?int $userId = null,
    ): void {
        try {
            $request ??= request();

            AuditLog::create([
                'user_id' => $userId ?? Auth::id(),
                'action' => $action,
                'module' => $module,
                'description' => Str::limit($description, 500),
                'method' => $request?->method(),
                'url' => $request ? Str::limit($request->fullUrl(), 500) : null,
                'route_name' => $request?->route()?->getName(),
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? Str::limit((string) $request->userAgent(), 1000) : null,
                'properties' => $properties ? self::sanitize($properties) : null,
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);

            if (app()->runningUnitTests()) {
                throw $e;
            }
        }
    }

    public static function fromRequest(Request $request): void
    {
        $routeName = (string) ($request->route()?->getName() ?? '');
        $action = self::resolveAction($request, $routeName);
        $module = self::resolveModule($routeName);
        $description = self::resolveDescription($request, $action, $module, $routeName);

        self::log(
            action: $action,
            description: $description,
            module: $module,
            properties: array_merge(
                $request->except(self::SENSITIVE_KEYS),
                ['_query' => $request->query()]
            ),
            request: $request,
        );
    }

    private static function resolveAction(Request $request, string $routeName): string
    {
        if (str_contains($routeName, '.close') || str_ends_with($routeName, '.close')) {
            return 'closed';
        }

        if (str_contains($routeName, '.verify') || str_ends_with($routeName, '.verify')) {
            return 'verified';
        }

        if (str_contains($routeName, '.credits.pay') || str_ends_with($routeName, '.pay')) {
            return 'paid';
        }

        if (str_ends_with($routeName, '.pdf') || str_ends_with($routeName, '.csv') || str_contains($routeName, '.ledger.pdf')) {
            return 'exported';
        }

        $method = strtoupper($request->method());

        if ($method === 'GET') {
            return 'viewed';
        }

        return match ($method) {
            'POST' => 'created',
            'PUT', 'PATCH' => 'updated',
            'DELETE' => 'deleted',
            default => 'other',
        };
    }

    private static function resolveModule(string $routeName): string
    {
        if ($routeName === '') {
            return 'system';
        }

        if (str_starts_with($routeName, 'reports.')) {
            return 'reports';
        }

        if (str_starts_with($routeName, 'employees.ledger')) {
            return 'employees';
        }

        $parts = explode('.', $routeName);
        $module = $parts[0] === 'mobil-oil' && isset($parts[1])
            ? 'mobil-oil.'.$parts[1]
            : $parts[0];

        return Str::limit($module, 100, '');
    }

    private static function resolveDescription(
        Request $request,
        string $action,
        string $module,
        string $routeName,
    ): string {
        $moduleLabel = AuditLog::moduleLabel($module);
        $actionLabel = AuditLog::actionLabels()[$action] ?? ucfirst($action);

        $id = self::resolveResourceId($request);
        $details = self::detailSummary($request);
        $idPart = $id ? " #{$id}" : '';
        $detailPart = $details !== '' ? " — {$details}" : '';

        if ($action === 'exported') {
            $format = str_ends_with($routeName, '.csv') ? 'CSV' : 'PDF';

            return trim("Exported {$moduleLabel} {$format}{$detailPart}");
        }

        if ($action === 'viewed') {
            $page = self::pageLabel($routeName, $moduleLabel);

            return trim("Viewed {$page}{$detailPart}");
        }

        if ($action === 'paid') {
            return trim("Recorded payment on {$moduleLabel}{$idPart}{$detailPart}");
        }

        return trim("{$actionLabel} {$moduleLabel}{$idPart}{$detailPart}");
    }

    private static function resolveResourceId(Request $request): mixed
    {
        $keys = [
            'id',
            'employee',
            'employee_salary',
            'tank',
            'dispenser',
            'nozzle',
            'expense',
            'cash_transaction',
            'agency_customer',
            'credit',
            'product',
            'employee_attendance',
            'tank_refill',
            'tank_dip_reading',
            'owner_fuel_usage',
        ];

        foreach ($keys as $key) {
            $value = $request->route($key);
            if ($value === null) {
                continue;
            }

            if (is_object($value) && isset($value->id)) {
                return $value->id;
            }

            if (is_scalar($value)) {
                return $value;
            }
        }

        return null;
    }

    private static function pageLabel(string $routeName, string $moduleLabel): string
    {
        if ($routeName === 'dashboard') {
            return 'Dashboard';
        }

        if ($routeName === 'employees.ledger') {
            return 'Employee Payment Ledger';
        }

        if (str_starts_with($routeName, 'reports.')) {
            $report = Str::headline(str_replace(['reports.', '-', '_'], ['', ' ', ' '], $routeName));

            return "Report: {$report}";
        }

        if (str_ends_with($routeName, '.index')) {
            return "{$moduleLabel} list";
        }

        if (str_ends_with($routeName, '.create')) {
            return "{$moduleLabel} create form";
        }

        if (str_ends_with($routeName, '.edit') || str_ends_with($routeName, '.close-form')) {
            return "{$moduleLabel} edit form";
        }

        if (str_ends_with($routeName, '.show')) {
            return "{$moduleLabel} details";
        }

        return $moduleLabel;
    }

    private static function detailSummary(Request $request): string
    {
        $parts = [];
        $input = $request->all();

        foreach (self::DETAIL_KEYS as $key) {
            if (! array_key_exists($key, $input) || $input[$key] === null || $input[$key] === '') {
                continue;
            }

            $value = $input[$key];
            if (is_array($value)) {
                continue;
            }

            $label = Str::headline(str_replace('_', ' ', $key));
            $parts[] = "{$label}: {$value}";

            if (count($parts) >= 4) {
                break;
            }
        }

        return implode(', ', $parts);
    }

    private static function sanitize(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_KEYS, true)) {
                continue;
            }

            if (is_array($value)) {
                $clean[$key] = self::sanitize($value);
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $clean[$key] = is_string($value) ? Str::limit($value, 500) : $value;
            }
        }

        return $clean;
    }
}
