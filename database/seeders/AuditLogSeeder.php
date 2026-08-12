<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Demo activity trail for local/dev.
 *
 *   php artisan db:seed --class=AuditLogSeeder
 */
class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = (int) (User::query()->where('email', 'admin@example.com')->value('id')
            ?? User::query()->value('id')
            ?? 0);

        if ($adminId === 0) {
            $this->command?->warn('AuditLogSeeder skipped: no users found. Run AdminUserSeeder first.');

            return;
        }

        $agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) FuelStationSeeder/1.0';
        $ip = '127.0.0.1';

        $entries = [
            ['logged_in', 'auth', 'User logged in: admin@example.com', 'POST', '/login', 'login', 14, 8, 5],
            ['viewed', 'dashboard', 'Viewed Dashboard', 'GET', '/dashboard', 'dashboard', 14, 8, 6],
            ['created', 'product-prices', 'Created Fuel Prices — Price: 381', 'POST', '/product-prices', 'product-prices.store', 13, 9, 10],
            ['created', 'employees', 'Created Employees — Name: Demo Attendant, Employee Code: EMP-01', 'POST', '/employees', 'employees.store', 13, 9, 25],
            ['viewed', 'reports', 'Viewed Report: Daily Sales', 'GET', '/reports/daily-sales', 'reports.daily-sales', 12, 10, 0],
            ['created', 'tank-refills', 'Created Tank Refills — Liters: 8000', 'POST', '/tank-refills', 'tank-refills.store', 12, 10, 15],
            ['created', 'employee-shifts', 'Created Employee Shifts', 'POST', '/employee-shifts', 'employee-shifts.store', 12, 9, 30],
            ['closed', 'employee-shifts', 'Closed Employee Shifts #1', 'POST', '/employee-shifts/1/close', 'employee-shifts.close', 12, 20, 45],
            ['created', 'expenses', 'Created Expenses — Expense Type: Utilities, Amount: 2500', 'POST', '/expenses', 'expenses.store', 11, 14, 20],
            ['created', 'cash-transactions', 'Created Cash In / Out — Type: out, Amount: 25000', 'POST', '/cash-transactions', 'cash-transactions.store', 11, 15, 5],
            ['exported', 'reports', 'Exported Reports PDF', 'GET', '/reports/profit-loss/pdf', 'reports.profit-loss.pdf', 10, 16, 0],
            ['created', 'employee-salaries', 'Created Employee Salaries — Type: advance, Amount: 5000', 'POST', '/employee-salaries', 'employee-salaries.store', 8, 11, 40],
            ['viewed', 'employees', 'Viewed Employee Payment Ledger', 'GET', '/employees/1/ledger', 'employees.ledger', 8, 11, 50],
            ['updated', 'employees', 'Updated Employees #1 — Phone: 03001234567', 'PUT', '/employees/1', 'employees.update', 7, 16, 10],
            ['created', 'mobil-oil.sales', 'Created Mobil Oil Sales — Amount: 4500', 'POST', '/mobil-oil/sales', 'mobil-oil.sales.store', 6, 13, 25],
            ['created', 'agency-customers', 'Created Agency Customers — Name: City Transport', 'POST', '/agency-customers', 'agency-customers.store', 5, 10, 0],
            ['paid', 'agency-customers', 'Recorded payment on Agency Customers #2 — Amount: 10000', 'POST', '/agency-credits/2/payments', 'agency-customers.credits.pay', 5, 10, 20],
            ['verified', 'employee-shifts', 'Verified Employee Shifts #2', 'POST', '/employee-shifts/2/verify', 'employee-shifts.verify', 4, 21, 15],
            ['exported', 'employees', 'Exported Employees PDF', 'GET', '/employees/1/ledger/pdf', 'employees.ledger.pdf', 3, 12, 30],
            ['created', 'employee-salaries', 'Created Employee Salaries — Type: full, Amount: 30000', 'POST', '/employee-salaries', 'employee-salaries.store', 1, 12, 0],
            ['updated', 'expenses', 'Updated Expenses #3 — Amount: 2800', 'PUT', '/expenses/3', 'expenses.update', 1, 15, 30],
            ['viewed', 'reports', 'Viewed Report: Profit Loss', 'GET', '/reports/profit-loss', 'reports.profit-loss', 1, 16, 0],
            ['logged_out', 'auth', 'User logged out: admin@example.com', 'POST', '/logout', 'logout', 1, 22, 0],
            ['logged_in', 'auth', 'User logged in: admin@example.com', 'POST', '/login', 'login', 0, 8, 12],
            ['viewed', 'dashboard', 'Viewed Dashboard', 'GET', '/dashboard', 'dashboard', 0, 8, 13],
            ['created', 'tank-dip-readings', 'Created Tank Dip Readings', 'POST', '/tank-dip-readings', 'tank-dip-readings.store', 0, 9, 5],
            ['created', 'cash-transactions', 'Created Cash In / Out — Type: in, Amount: 5000', 'POST', '/cash-transactions', 'cash-transactions.store', 0, 10, 20],
            ['deleted', 'expenses', 'Deleted Expenses #9', 'DELETE', '/expenses/9', 'expenses.destroy', 0, 11, 0],
            ['exported', 'reports', 'Exported Reports CSV', 'GET', '/reports/daily-sales/csv', 'reports.daily-sales.csv', 0, 11, 30],
        ];

        foreach ($entries as [$action, $module, $description, $method, $url, $route, $daysAgo, $hour, $minute]) {
            $at = Carbon::now()->subDays($daysAgo)->setTime($hour, $minute, 0);

            AuditLog::create([
                'user_id' => $adminId,
                'action' => $action,
                'module' => $module,
                'description' => $description,
                'method' => $method,
                'url' => url($url),
                'route_name' => $route,
                'ip_address' => $ip,
                'user_agent' => $agent,
                'properties' => [
                    'seeded' => true,
                    'module' => $module,
                    'action' => $action,
                ],
                'created_at' => $at,
            ]);
        }

        $this->command?->info('Activity logs seeded: '.count($entries).' demo entries.');
    }
}
