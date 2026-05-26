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
use App\Services\BusinessDayService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index()
    {
        $businessDate = BusinessDayService::currentBusinessDate();
        $businessDateStr = $businessDate->toDateString();
        $monthStart = $businessDate->copy()->startOfMonth();

        $counts = [
            'products' => Product::count(),
            'tanks' => Tank::count(),
            'dispensers' => Dispenser::count(),
            'nozzles' => Nozzle::count(),
            'employees' => Employee::count(),
            'activeShifts' => EmployeeShift::where('status', 'active')
                ->where('assigned_date', $businessDateStr)
                ->count(),
        ];

        $todaySales = (float) EmployeeShift::where('assigned_date', $businessDateStr)
            ->whereIn('status', ['submitted', 'verified'])
            ->sum('total_amount');
        $todayLiters = (float) EmployeeShift::where('assigned_date', $businessDateStr)
            ->whereIn('status', ['submitted', 'verified'])
            ->sum('total_liters');
        $todayShiftCount = EmployeeShift::where('assigned_date', $businessDateStr)->count();
        $todayCash = (float) EmployeeShift::where('assigned_date', $businessDateStr)
            ->whereIn('status', ['submitted', 'verified'])
            ->sum('cash_received');
        $todayOnline = (float) EmployeeShift::where('assigned_date', $businessDateStr)
            ->whereIn('status', ['submitted', 'verified'])
            ->sum('online_received');

        $todayExpense = (float) Expense::whereDate('expense_date', $businessDateStr)->sum('amount');
        [$dayFrom, $dayTo] = BusinessDayService::businessDayBounds($businessDateStr);
        $todayOwnerFuel = (float) OwnerFuelUsage::whereBetween('usage_datetime', [$dayFrom, $dayTo])->sum('total_amount');
        $todayNet = $todaySales - $todayExpense - $todayOwnerFuel;

        $mtdSales = (float) EmployeeShift::whereBetween('assigned_date', [$monthStart->toDateString(), $businessDateStr])
            ->whereIn('status', ['submitted', 'verified'])
            ->sum('total_amount');
        $mtdLiters = (float) EmployeeShift::whereBetween('assigned_date', [$monthStart->toDateString(), $businessDateStr])
            ->sum('total_liters');
        $mtdExpense = (float) Expense::whereBetween('expense_date', [$monthStart->toDateString(), $businessDateStr])->sum('amount');
        $mtdOwnerFuel = (float) OwnerFuelUsage::whereBetween('usage_datetime', [$monthStart->copy()->setTime(9, 0), $dayTo])->sum('total_amount');
        $mtdNet = $mtdSales - $mtdExpense - $mtdOwnerFuel;
        $mtdRefills = (float) TankRefill::whereBetween('received_datetime', [$monthStart, $dayTo])->sum('total_amount');

        $trend = $this->buildDailyTrend(7);
        $expenseByType = Expense::where('expense_date', '>=', $businessDate->copy()->subDays(29))
            ->selectRaw('expense_type, SUM(amount) as total')
            ->groupBy('expense_type')
            ->orderByDesc('total')
            ->get();

        $salesByProduct = $this->buildSalesByProduct(7);
        $topEmployees = $this->buildTopEmployees(7, $businessDate);

        $tankStock = Tank::with('product')->orderBy('tank_number')->get()->map(function ($tank) {
            $capacity = (float) $tank->capacity_liters;
            $stock = (float) $tank->current_stock_liters;

            return [
                'label' => $tank->tank_number . ' (' . ($tank->product->name ?? 'N/A') . ')',
                'tank_number' => $tank->tank_number,
                'product' => $tank->product->name ?? 'N/A',
                'stock' => $stock,
                'capacity' => $capacity,
                'fill_percent' => $capacity > 0 ? round(($stock / $capacity) * 100, 1) : 0,
                'is_low' => $stock <= (float) $tank->minimum_level,
            ];
        });

        $lowStockTanks = $tankStock->where('is_low', true)->values();
        $totalTankStock = $tankStock->sum('stock');
        $totalTankCapacity = $tankStock->sum('capacity');

        $recentShifts = EmployeeShift::with(['employee', 'nozzle'])
            ->latest('assigned_date')
            ->latest('id')
            ->take(6)
            ->get();

        $recentExpenses = Expense::latest('expense_date')->take(6)->get();
        $recentOwnerFuel = OwnerFuelUsage::with('product')->latest('usage_datetime')->take(5)->get();

        return view('dashboard', array_merge($counts, compact(
            'businessDateStr',
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
            $businessDate = BusinessDayService::currentBusinessDate()->copy()->subDays($i);
            $dateStr = $businessDate->toDateString();
            $labels[] = $businessDate->format('d M');

            [$from, $to] = BusinessDayService::businessDayBounds($dateStr);

            $daySales = (float) EmployeeShift::where('assigned_date', $dateStr)
                ->whereIn('status', ['submitted', 'verified'])
                ->sum('total_amount');
            $dayLiters = (float) EmployeeShift::where('assigned_date', $dateStr)->sum('total_liters');
            $dayExpense = (float) Expense::whereDate('expense_date', $dateStr)->sum('amount');
            $dayOwnerFuel = (float) OwnerFuelUsage::whereBetween('usage_datetime', [$from, $to])->sum('total_amount');

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
        $from = BusinessDayService::currentBusinessDate()->copy()->subDays($days - 1)->toDateString();

        return EmployeeShift::with('nozzle.product')
            ->where('assigned_date', '>=', $from)
            ->whereIn('status', ['submitted', 'verified'])
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

    private function buildTopEmployees(int $days, Carbon $businessDate): Collection
    {
        $from = $businessDate->copy()->subDays($days - 1)->toDateString();

        return EmployeeShift::with('employee')
            ->where('assigned_date', '>=', $from)
            ->whereIn('status', ['submitted', 'verified'])
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
    }
}
