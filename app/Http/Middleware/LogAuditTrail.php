<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAuditTrail
{
    /**
     * Skip noisy / sensitive routes entirely.
     */
    private const SKIP_ROUTE_PREFIXES = [
        'audit-logs.',
        'data-management.',
        'password.',
        'verification.',
        'profile.',
    ];

    /**
     * Skip routine GET browsing (lists/forms) — mutations + exports + key screens still log.
     * Empty = log every authenticated GET too (very noisy).
     */
    private const SKIP_GET_ROUTE_SUFFIXES = [
        '.index',
        '.create',
        '.edit',
        '.close-form',
        '.show',
    ];

    private const ALWAYS_LOG_GET_ROUTES = [
        'dashboard',
        'employees.ledger',
        'reports.dashboard',
        'reports.daily-sales',
        'reports.profit-loss',
        'reports.stock',
        'reports.expenses',
        'reports.variance',
        'reports.attendance',
        'reports.mobil-oil-sales',
        'reports.cash',
        'reports.purchases',
        'reports.shifts',
        'reports.employee-salaries',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldLog($request, $response)) {
            AuditLogger::fromRequest($request);
        }

        return $response;
    }

    private function shouldLog(Request $request, Response $response): bool
    {
        if (! $request->user()) {
            return false;
        }

        if ($response->getStatusCode() >= 400) {
            return false;
        }

        $routeName = (string) ($request->route()?->getName() ?? '');
        $method = strtoupper($request->method());

        foreach (self::SKIP_ROUTE_PREFIXES as $prefix) {
            if ($routeName !== '' && str_starts_with($routeName, $prefix)) {
                return false;
            }
        }

        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return true;
        }

        if ($method !== 'GET' || $routeName === '') {
            return false;
        }

        if (str_ends_with($routeName, '.pdf') || str_ends_with($routeName, '.csv')) {
            return true;
        }

        if (in_array($routeName, self::ALWAYS_LOG_GET_ROUTES, true)) {
            return true;
        }

        foreach (self::SKIP_GET_ROUTE_SUFFIXES as $suffix) {
            if (str_ends_with($routeName, $suffix)) {
                return false;
            }
        }

        return false;
    }
}
