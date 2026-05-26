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
            str_starts_with($name, 'products.') => match ($name) {
                'products.edit' => 'Edit Product',
                default => 'Products',
            },
            str_starts_with($name, 'product-prices.') => match ($name) {
                'product-prices.create' => 'Set Product Price',
                default => 'Product Prices',
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
                default => 'Employees',
            },
            str_starts_with($name, 'employee-shifts.') => match ($name) {
                'employee-shifts.create' => 'Assign Shift',
                'employee-shifts.close-form' => 'Close Shift',
                default => 'Employee Shifts',
            },
            str_starts_with($name, 'tank-refills.') => match ($name) {
                'tank-refills.create' => 'Add Tank Refill',
                default => 'Tank Refills',
            },
            str_starts_with($name, 'tank-dip-readings.') => match ($name) {
                'tank-dip-readings.create' => 'Add Dip Reading',
                default => 'Tank Dip Readings',
            },
            str_starts_with($name, 'owner-fuel-usages.') => match ($name) {
                'owner-fuel-usages.create' => 'Owner Fuel Usage',
                default => 'Owner Fuel Usage',
            },
            str_starts_with($name, 'expenses.') => match ($name) {
                'expenses.create' => 'Add Expense',
                default => 'Expenses',
            },
            str_starts_with($name, 'reports.') => match ($name) {
                'reports.dashboard' => 'Reports',
                'reports.daily-sales' => 'Daily Sales Report',
                'reports.profit-loss' => 'Profit & Loss Report',
                'reports.stock' => 'Stock Report',
                'reports.expenses' => 'Expense Report',
                'reports.variance' => 'Variance Report',
                default => 'Reports',
            },
            default => 'Fuel Station',
        };
    }
}
