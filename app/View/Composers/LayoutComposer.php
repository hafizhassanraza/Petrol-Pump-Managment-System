<?php

namespace App\View\Composers;

use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class LayoutComposer
{
    public function compose(View $view): void
    {
        $view->with('pageTitle', $this->resolvePageTitle());
    }

    private function resolvePageTitle(): string
    {
        $name = Route::currentRouteName() ?? '';

        return match (true) {
            $name === 'home' => 'Home',
            $name === 'dashboard' => 'Dashboard',
            str_starts_with($name, 'product-prices.') => match ($name) {
                'product-prices.create' => 'Set Fuel Price',
                default => 'Fuel Prices',
            },
            str_starts_with($name, 'tanks.') => match ($name) {
                'tanks.create' => 'Add Tank',
                'tanks.edit' => 'Edit Tank',
                default => 'Tanks',
            },
            str_starts_with($name, 'dispensers.') => match ($name) {
                'dispensers.create' => 'Add Dispenser',
                'dispensers.edit' => 'Edit Dispenser',
                default => 'Dispensers',
            },
            str_starts_with($name, 'nozzles.') => match ($name) {
                'nozzles.create' => 'Add Nozzle',
                'nozzles.edit' => 'Edit Nozzle',
                default => 'Nozzles',
            },
            str_starts_with($name, 'employees.') => match ($name) {
                'employees.create' => 'Add Employee',
                'employees.edit' => 'Edit Employee',
                'employees.ledger' => 'Employee Payment Ledger',
                default => 'Employees',
            },
            str_starts_with($name, 'employee-attendances.') => match ($name) {
                'employee-attendances.create' => 'Mark Attendance',
                'employee-attendances.edit' => 'Edit Attendance',
                default => 'Employee Attendance',
            },
            str_starts_with($name, 'employee-salaries.') => match ($name) {
                'employee-salaries.create' => 'Add Salary Payment',
                'employee-salaries.edit' => 'Edit Salary Payment',
                default => 'Employee Salaries',
            },
            str_starts_with($name, 'employee-shifts.') => match ($name) {
                'employee-shifts.create' => 'Assign Shift',
                'employee-shifts.edit' => 'Edit Shift',
                'employee-shifts.close-form' => 'Close Shift',
                default => 'Employee Shifts',
            },
            str_starts_with($name, 'tank-refills.') => match ($name) {
                'tank-refills.create' => 'Add Tank Refill',
                'tank-refills.edit' => 'Edit Tank Refill',
                default => 'Tank Refills',
            },
            str_starts_with($name, 'tank-dip-readings.') => match ($name) {
                'tank-dip-readings.create' => 'Add Dip Reading',
                default => 'Tank Dip Readings',
            },
            str_starts_with($name, 'owner-fuel-usages.') => 'Owner Fuel Usage',
            str_starts_with($name, 'agency-customers.') => match ($name) {
                'agency-customers.create' => 'Add Agency Customer',
                'agency-customers.edit' => 'Edit Agency Customer',
                'agency-customers.show' => 'Agency Customer Credits',
                default => 'Agency Customers',
            },
            str_starts_with($name, 'expenses.') => match ($name) {
                'expenses.create' => 'Add Expense',
                'expenses.edit' => 'Edit Expense',
                default => 'Expenses',
            },
            str_starts_with($name, 'cash-transactions.') => match ($name) {
                'cash-transactions.create' => 'Add Cash Transaction',
                'cash-transactions.edit' => 'Edit Cash Transaction',
                default => 'Cash In / Out',
            },
            str_starts_with($name, 'audit-logs.') => 'Activity Logs',
            str_starts_with($name, 'mobil-oil.products.') => match ($name) {
                'mobil-oil.products.create' => 'Add Mobil Oil Product',
                'mobil-oil.products.edit' => 'Edit Mobil Oil Product',
                default => 'Mobil Oil Products',
            },
            str_starts_with($name, 'mobil-oil.purchases.') => match ($name) {
                'mobil-oil.purchases.create' => 'Record Mobil Oil Purchase',
                default => 'Mobil Oil Purchases',
            },
            str_starts_with($name, 'mobil-oil.sales.') => match ($name) {
                'mobil-oil.sales.create' => 'Record Mobil Oil Sale',
                default => 'Mobil Oil Sales',
            },
            str_starts_with($name, 'reports.') => match ($name) {
                'reports.dashboard' => 'Reports',
                'reports.daily-sales' => 'Daily Sales Report',
                'reports.profit-loss' => 'Profit & Loss Report',
                'reports.stock' => 'Stock Report',
                'reports.expenses' => 'Expense Report',
                'reports.employee-salaries' => 'Employee Salaries Report',
                'reports.variance' => 'Variance Report',
                'reports.attendance' => 'Attendance Report',
                'reports.mobil-oil-sales' => 'Mobil Oil Sales Report',
                default => 'Reports',
            },
            default => 'Fuel Station',
        };
    }
}
