<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Tank;
use App\Models\Dispenser;
use App\Models\Nozzle;
use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\Expense;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /* public function index()
    {
        return view('dashboard', [

            'products' => Product::count(),

            'tanks' => Tank::count(),

            'dispensers' => Dispenser::count(),

            'nozzles' => Nozzle::count(),

            'employees' => Employee::count(),

            'activeShifts' => EmployeeShift::where('status', 'active')->count(),

            'todaySales' => EmployeeShift::whereDate(
                'created_at',
                today()
            )->sum('total_amount'),

        ]);
    } */

    public function index()
    {
        $products = \App\Models\Product::count();
        $tanks = \App\Models\Tank::count();
        $dispensers = \App\Models\Dispenser::count();
        $nozzles = \App\Models\Nozzle::count();
        $employees = \App\Models\Employee::count();
        $activeShifts = \App\Models\EmployeeShift::where('status', 'active')->count();

        $todaySales = EmployeeShift::whereDate('created_at', today())
            ->sum('total_amount');

        $todayExpense = Expense::whereDate('expense_date', today())
            ->sum('amount');

        // 📊 LAST 7 DAYS SALES
        $salesLabels = [];
        $salesData = [];

        for ($i = 6; $i >= 0; $i--) {

            $date = Carbon::today()->subDays($i)->toDateString();

            $salesLabels[] = $date;

            $salesData[] = EmployeeShift::whereDate('created_at', $date)
                ->sum('total_amount');
        }

        return view('dashboard', compact(
            'products',
            'tanks',
            'dispensers',
            'nozzles',
            'employees',
            'activeShifts',
            'todaySales',
            'todayExpense',
            'salesLabels',
            'salesData'
        ));
    }
}