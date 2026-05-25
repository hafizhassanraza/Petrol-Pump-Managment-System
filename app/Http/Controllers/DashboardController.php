<?php

namespace App\Http\Controllers;

use App\Models\Dispenser;
use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\Expense;
use App\Models\Nozzle;
use App\Models\OwnerFuelUsage;
use App\Models\Product;
use App\Models\Tank;
use App\Models\TankRefill;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index()
    {
        $today = today();
        $monthStart = $today->copy()->startOfMonth();

        // --- Inventory counts ---
        $counts = [
            'products' => Product::count(),
            'tanks' => Tank::count(),
            'dispensers' => Dispenser::count(),
            'nozzles' => Nozzle::count(),
            'employees' => Employee::count(),
            'activeShifts' => EmployeeShift::where('status', 'active')->count(),
        ];

        // --- Today financials ---
        $todaySales = (float) EmployeeShift::whereDate('created_at', $today)->sum('total_amount');
        $todayLiters = (float) EmployeeShift::whereDate('created_at', $today)->sum('total_liters');
        $todayExpense = (float) Expense::whereDate('expense_date', $today)->sum('amount');
        $todayOwnerFuel = (float) OwnerFuelUsage::whereDate('usage_datetime', $today)->sum('total_amount');
        $todayShiftCount = EmployeeShift::whereDate('created_at', $today)->count();
        $todayCash = (float) EmployeeShift::whereDate('created_at', $today)->sum('cash_received');
        $todayOnline = (float) EmployeeShift::whereDate('created_at', $today)->sum('online_received');
        $todayNet = $todaySales - $todayExpense - $todayOwnerFuel;

        // --- Month to date ---
        $mtdSales = (float) EmployeeShift::whereBetween('created_at', [$monthStart, $today->endOfDay()])->sum('total_amount');
        $mtdLiters = (float) EmployeeShift::whereBetween('created_at', [$monthStart, $today->endOfDay()])->sum('total_liters');
        $mtdExpense = (float) Expense::whereBetween('expense_date', [$monthStart->toDateString(), $today->toDateString()])->sum('amount');
        $mtdOwnerFuel = (float) OwnerFuelUsage::whereBetween('usage_datetime', [$monthStart, $today->endOfDay()])->sum('total_amount');
        $mtdNet = $mtdSales - $mtdExpense - $mtdOwnerFuel;
        $mtdRefills = (float) TankRefill::whereBetween('received_datetime', [$monthStart, $today->endOfDay()])->sum('total_amount');

        // --- Last 7 days trend ---
        $trend = $this->buildDailyTrend(7);

        // --- Expense by type (last 30 days) ---
        $expenseByType = Expense::where('expense_date', '>=', $today->copy()->subDays(29))
            ->selectRaw('expense_type, SUM(amount) as total')
            ->groupBy('expense_type')
            ->orderByDesc('total')
            ->get();

        // --- Sales by product (last 7 days) ---
        $salesByProduct = $this->buildSalesByProduct(7);

        // --- Top employees (last 7 days) ---
        $topEmployees = EmployeeShift::with('employee')
            ->where('created_at', '>=', $today->copy()->subDays(6)->startOfDay())
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($group) => [
                'name' => $group->first()->employee->name ?? 'Unknown',
                'amount' => $group->sum('total_amount'),
                'liters' => $group->sum('total_liters'),
                'shifts' => $group->count(),
            ])
            ->sortByDesc('amount')
            ->take(5)
            ->values();

        // --- Tank stock ---
        $tankStock = Tank::with('product')->orderBy('tank_number')->get()->map(function ($tank) {
            $capacity = (float) $tank->capacity_liters;
            $stock = (float) $tank->current_stock_liters;
            $minimum = (float) $tank->minimum_level;

            return [
                'label' => $tank->tank_number . ' (' . ($tank->product->name ?? 'N/A') . ')',
                'tank_number' => $tank->tank_number,
                'product' => $tank->product->name ?? 'N/A',
                'stock' => $stock,
                'capacity' => $capacity,
                'fill_percent' => $capacity > 0 ? round(($stock / $capacity) * 100, 1) : 0,
                'is_low' => $stock <= $minimum,
            ];
        });

        $lowStockTanks = $tankStock->where('is_low', true)->values();
        $totalTankStock = $tankStock->sum('stock');
        $totalTankCapacity = $tankStock->sum('capacity');

        // --- Recent activity ---
        $recentShifts = EmployeeShift::with(['employee', 'nozzle'])
            ->latest()
            ->take(6)
            ->get();

        $recentExpenses = Expense::latest('expense_date')->take(6)->get();

        $recentOwnerFuel = OwnerFuelUsage::with('product')
            ->latest('usage_datetime')
            ->take(5)
            ->get();

        return view('dashboard', array_merge($counts, compact(
            'todaySales',
            'todayLiters',
            'todayExpense',
            'todayOwnerFuel',
            'todayShiftCount',
            'todayCash',
            'todayOnline',
            'todayNet',
            'mtdSales',
            'mtdLiters',
            'mtdExpense',
            'mtdOwnerFuel',
            'mtdNet',
            'mtdRefills',
            'trend',
            'expenseByType',
            'salesByProduct',
            'topEmployees',
            'tankStock',
            'lowStockTanks',
            'totalTankStock',
            'totalTankCapacity',
            'recentShifts',
            'recentExpenses',
            'recentOwnerFuel'
        )));
    }

    private function buildDailyTrend(int $days): array
    {
        $labels = [];
        $sales = [];
        $expenses = [];
        $ownerFuel = [];
        $net = [];
        $liters = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateStr = $date->toDateString();
            $labels[] = $date->format('d M');

            $daySales = (float) EmployeeShift::whereDate('created_at', $dateStr)->sum('total_amount');
            $dayExpense = (float) Expense::whereDate('expense_date', $dateStr)->sum('amount');
            $dayOwnerFuel = (float) OwnerFuelUsage::whereDate('usage_datetime', $dateStr)->sum('total_amount');
            $dayLiters = (float) EmployeeShift::whereDate('created_at', $dateStr)->sum('total_liters');

            $sales[] = $daySales;
            $expenses[] = $dayExpense;
            $ownerFuel[] = $dayOwnerFuel;
            $liters[] = $dayLiters;
            $net[] = $daySales - $dayExpense - $dayOwnerFuel;
        }

        return compact('labels', 'sales', 'expenses', 'ownerFuel', 'net', 'liters');
    }

    private function buildSalesByProduct(int $days): Collection
    {
        $from = Carbon::today()->subDays($days - 1)->startOfDay();

        return EmployeeShift::with('nozzle.product')
            ->where('created_at', '>=', $from)
            ->get()
            ->groupBy(fn ($shift) => $shift->nozzle->product->name ?? 'Unknown')
            ->map(fn ($group, $name) => [
                'product' => $name,
                'amount' => (float) $group->sum('total_amount'),
                'liters' => (float) $group->sum('total_liters'),
            ])
            ->sortByDesc('amount')
            ->values();
    }
}
