<?php

namespace App\Http\Controllers;

use App\Models\Dispenser;
use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\EmployeeSalary;
use App\Models\Expense;
use App\Models\MobilOilSale;
use App\Models\Nozzle;
use App\Models\OwnerFuelUsage;
use App\Models\Tank;
use App\Models\TankRefill;
use App\Services\BusinessDayService;
use App\Support\FuelProducts;
use App\Support\ReportRange;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $range = ReportRange::fromRequest($request);
        $from = $range['from'];
        $to = $range['to'];
        $fromAt = $range['fromAt'];
        $toAt = $range['toAt'];

        $businessDate = BusinessDayService::currentBusinessDate();
        $businessDateStr = $businessDate->toDateString();
        $monthStart = $businessDate->copy()->startOfMonth()->toDateString();

        $periodLabel = $this->periodLabel($range['filter'], $from, $to);

        $counts = [
            'products' => FuelProducts::all()->count(),
            'tanks' => Tank::count(),
            'dispensers' => Dispenser::count(),
            'nozzles' => Nozzle::count(),
            'employees' => Employee::count(),
            'activeShifts' => EmployeeShift::where('status', 'active')->count(),
        ];

        $shiftQuery = fn () => EmployeeShift::closedBetween($from, $to)
            ->whereIn('status', ['submitted', 'verified']);

        $periodSales = (float) $shiftQuery()->sum('total_amount');
        $periodLiters = (float) $shiftQuery()->sum('total_liters');
        $periodShiftCount = EmployeeShift::closedBetween($from, $to)->count();
        $periodCash = (float) $shiftQuery()->sum('cash_received');
        $periodOnline = (float) $shiftQuery()->sum('online_received');

        $periodExpense = (float) Expense::operating()
            ->whereBetween('expense_date', [$from, $to])
            ->sum('amount');
        $periodSalary = (float) EmployeeSalary::whereBetween('payment_date', [$from, $to])->sum('amount');
        $periodOwnerFuel = (float) OwnerFuelUsage::whereBetween('usage_datetime', [$fromAt, $toAt])->sum('total_amount');
        $periodRefills = (float) TankRefill::whereBetween('received_datetime', [$fromAt, $toAt])->sum('total_amount');
        $periodMobilOilSales = (float) MobilOilSale::whereBetween('sold_datetime', [$fromAt, $toAt])->sum('total_amount');
        $periodNet = $periodSales + $periodMobilOilSales - $periodExpense - $periodSalary - $periodOwnerFuel;

        $mtdSales = (float) EmployeeShift::closedBetween($monthStart, $businessDateStr)
            ->whereIn('status', ['submitted', 'verified'])
            ->sum('total_amount');
        $mtdLiters = (float) EmployeeShift::closedBetween($monthStart, $businessDateStr)
            ->sum('total_liters');
        $mtdExpense = (float) Expense::operating()
            ->whereBetween('expense_date', [$monthStart, $businessDateStr])
            ->sum('amount');
        $mtdSalary = (float) EmployeeSalary::whereBetween('payment_date', [$monthStart, $businessDateStr])->sum('amount');
        [$monthFromAt, $monthToAt] = BusinessDayService::businessDayBounds($businessDateStr);
        $mtdOwnerFuel = (float) OwnerFuelUsage::whereBetween('usage_datetime', [
            Carbon::parse($monthStart)->setTime(9, 0),
            $monthToAt,
        ])->sum('total_amount');
        $mtdNet = $mtdSales - $mtdExpense - $mtdSalary - $mtdOwnerFuel;
        $mtdRefills = (float) TankRefill::whereBetween('received_datetime', [
            Carbon::parse($monthStart)->startOfDay(),
            $monthToAt,
        ])->sum('total_amount');

        $trend = $this->buildDailyTrend($from, $to);
        $chartDayCount = count($trend['labels']);

        $expenseByType = Expense::operating()
            ->whereBetween('expense_date', [$from, $to])
            ->selectRaw('expense_type, SUM(amount) as total')
            ->groupBy('expense_type')
            ->orderByDesc('total')
            ->get();

        $salesByProduct = $this->buildSalesByProduct($from, $to);
        $topEmployees = $this->buildTopEmployees($from, $to);

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
            ->closedBetween($from, $to)
            ->latest('closed_date')
            ->latest('id')
            ->take(6)
            ->get();

        $recentExpenses = Expense::operating()
            ->whereBetween('expense_date', [$from, $to])
            ->latest('expense_date')
            ->take(6)
            ->get();

        $recentSalaries = EmployeeSalary::with('employee')
            ->whereBetween('payment_date', [$from, $to])
            ->latest('payment_date')
            ->take(5)
            ->get();

        $recentOwnerFuel = OwnerFuelUsage::with('product')
            ->whereBetween('usage_datetime', [$fromAt, $toAt])
            ->latest('usage_datetime')
            ->take(5)
            ->get();

        return view('dashboard', array_merge($range, $counts, compact(
            'businessDateStr',
            'periodLabel',
            'periodSales',
            'periodLiters',
            'periodExpense',
            'periodSalary',
            'periodOwnerFuel',
            'periodRefills',
            'periodMobilOilSales',
            'periodShiftCount',
            'periodCash',
            'periodOnline',
            'periodNet',
            'mtdSales',
            'mtdLiters',
            'mtdExpense',
            'mtdSalary',
            'mtdOwnerFuel',
            'mtdNet',
            'mtdRefills',
            'trend',
            'chartDayCount',
            'expenseByType',
            'salesByProduct',
            'topEmployees',
            'tankStock',
            'lowStockTanks',
            'totalTankStock',
            'totalTankCapacity',
            'recentShifts',
            'recentExpenses',
            'recentSalaries',
            'recentOwnerFuel'
        )));
    }

    private function periodLabel(string $filter, string $from, string $to): string
    {
        return match ($filter) {
            'today' => 'Today (business day)',
            'last-week' => 'Last 7 days',
            'last-month' => 'Last 30 days',
            'custom' => Carbon::parse($from)->format('d M Y') . ' – ' . Carbon::parse($to)->format('d M Y'),
            default => Carbon::parse($from)->format('d M Y') . ' – ' . Carbon::parse($to)->format('d M Y'),
        };
    }

    private function buildDailyTrend(string $from, string $to): array
    {
        $labels = [];
        $sales = [];
        $expenses = [];
        $salaries = [];
        $ownerFuel = [];
        $net = [];
        $liters = [];

        $start = Carbon::parse($from);
        $end = Carbon::parse($to);

        if ($start->diffInDays($end) > 60) {
            $start = $end->copy()->subDays(60);
        }

        foreach (CarbonPeriod::create($start, $end) as $day) {
            $dateStr = $day->toDateString();
            $labels[] = $day->format('d M');

            [$dayFrom, $dayTo] = BusinessDayService::businessDayBounds($dateStr);

            $daySales = (float) EmployeeShift::closedOn($dateStr)
                ->whereIn('status', ['submitted', 'verified'])
                ->sum('total_amount');
            $dayMobilOil = (float) MobilOilSale::whereBetween('sold_datetime', [$dayFrom, $dayTo])->sum('total_amount');
            $dayLiters = (float) EmployeeShift::closedOn($dateStr)->sum('total_liters');
            $dayExpense = (float) Expense::operating()->whereDate('expense_date', $dateStr)->sum('amount');
            $daySalary = (float) EmployeeSalary::whereDate('payment_date', $dateStr)->sum('amount');
            $dayOwnerFuel = (float) OwnerFuelUsage::whereBetween('usage_datetime', [$dayFrom, $dayTo])->sum('total_amount');

            $totalDaySales = $daySales + $dayMobilOil;

            $sales[] = $totalDaySales;
            $expenses[] = $dayExpense;
            $salaries[] = $daySalary;
            $ownerFuel[] = $dayOwnerFuel;
            $liters[] = $dayLiters;
            $net[] = $totalDaySales - $dayExpense - $daySalary - $dayOwnerFuel;
        }

        return compact('labels', 'sales', 'expenses', 'salaries', 'ownerFuel', 'net', 'liters');
    }

    private function buildSalesByProduct(string $from, string $to): Collection
    {
        $ids = FuelProducts::ids();
        $shifts = EmployeeShift::with('nozzle')
            ->closedBetween($from, $to)
            ->whereIn('status', ['submitted', 'verified'])
            ->get();

        return collect([
            [
                'product' => FuelProducts::PETROL,
                'amount' => (float) $shifts->filter(fn ($s) => (int) ($s->nozzle->product_id ?? 0) === $ids['petrol'])->sum('total_amount'),
                'liters' => (float) $shifts->filter(fn ($s) => (int) ($s->nozzle->product_id ?? 0) === $ids['petrol'])->sum('total_liters'),
            ],
            [
                'product' => FuelProducts::DIESEL,
                'amount' => (float) $shifts->filter(fn ($s) => (int) ($s->nozzle->product_id ?? 0) === $ids['diesel'])->sum('total_amount'),
                'liters' => (float) $shifts->filter(fn ($s) => (int) ($s->nozzle->product_id ?? 0) === $ids['diesel'])->sum('total_liters'),
            ],
        ]);
    }

    private function buildTopEmployees(string $from, string $to): Collection
    {
        return EmployeeShift::with('employee')
            ->closedBetween($from, $to)
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
