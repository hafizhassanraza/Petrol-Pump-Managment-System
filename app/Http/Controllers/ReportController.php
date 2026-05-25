<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmployeeShift;
use App\Models\Expense;
use App\Models\Tank;
use App\Models\OwnerFuelUsage;
use App\Models\TankDipReading;
use App\Models\TankRefill;
use PDF;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function dashboard()
    {
        return view('reports.dashboard');
    }

    private function getReportRange(Request $request): array
    {
        $filter = $request->filter ?? 'today';
        $from = $request->from;
        $to = $request->to;

        if ($filter === 'today') {
            $from = now()->format('Y-m-d');
            $to = now()->format('Y-m-d');
        } elseif ($filter === 'last-week') {
            $from = now()->subDays(7)->format('Y-m-d');
            $to = now()->format('Y-m-d');
        } elseif ($filter === 'last-month') {
            $from = now()->subDays(30)->format('Y-m-d');
            $to = now()->format('Y-m-d');
        } elseif ($request->from && $request->to) {
            // custom range
        } else {
            $from = now()->format('Y-m-d');
            $to = now()->format('Y-m-d');
        }

        return compact('filter', 'from', 'to');
    }

    /*
    |--------------------------------------------------------------------------
    | DAILY SALES
    |--------------------------------------------------------------------------
    */
    private function getDailySalesRange(Request $request)
    {
        return $this->getReportRange($request);
    }

    private function getDailySalesData(Request $request)
    {
        $range = $this->getDailySalesRange($request);

        $query = EmployeeShift::with('employee', 'nozzle');

        if ($range['from'] && $range['to']) {
            $query->whereBetween('created_at', [$range['from'] . ' 00:00:00', $range['to'] . ' 23:59:59']);
        }

        $shifts = $query->latest()->get();
        $totalAmount = $shifts->sum('total_amount');
        $totalLiters = $shifts->sum('total_liters');

        $dailyTotals = $shifts
            ->groupBy(fn ($shift) => $shift->created_at->format('Y-m-d'))
            ->map(function ($group, $date) {
                return [
                    'date' => $date,
                    'label' => \Carbon\Carbon::parse($date)->format('d M Y'),
                    'total_amount' => $group->sum('total_amount'),
                    'total_liters' => $group->sum('total_liters'),
                    'record_count' => $group->count(),
                ];
            })
            ->values();

        return array_merge($range, compact('shifts', 'totalAmount', 'totalLiters', 'dailyTotals'));
    }

    public function dailySales(Request $request)
    {
        return view('reports.daily_sales', $this->getDailySalesData($request));
    }

    public function dailySalesPdf(Request $request)
    {
        $data = $this->getDailySalesData($request);
        $pdf = PDF::loadView('reports.pdf.daily_sales', $data);

        return $pdf->download('daily-sales-report.pdf');
    }

    public function dailySalesCsv(Request $request)
    {
        $data = $this->getDailySalesData($request);
        $shifts = $data['shifts'];
        $dailyTotals = $data['dailyTotals'];
        $filename = 'daily-sales-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['Employee', 'Nozzle', 'Liters', 'Amount', 'Date'];

        $callback = function () use ($shifts, $dailyTotals, $data, $columns) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Daily Sales Report']);
            fputcsv($f, ['Range', $data['from'] . ' to ' . $data['to']]);
            fputcsv($f, ['Filter', ucfirst(str_replace('-', ' ', $data['filter']))]);
            fputcsv($f, ['Records', $shifts->count()]);
            fputcsv($f, []);
            fputcsv($f, $columns);

            foreach ($shifts as $s) {
                fputcsv($f, [
                    $s->employee->name ?? '',
                    $s->nozzle->nozzle_number ?? '',
                    number_format($s->total_liters, 2),
                    number_format($s->total_amount, 2),
                    $s->created_at->format('d-m-Y H:i'),
                ]);
            }

            fputcsv($f, []);
            fputcsv($f, ['Daily Totals']);
            fputcsv($f, ['Date', 'Liters', 'Amount', 'Records']);

            foreach ($dailyTotals as $day) {
                fputcsv($f, [
                    $day['label'],
                    number_format($day['total_liters'], 2),
                    number_format($day['total_amount'], 2),
                    $day['record_count'],
                ]);
            }

            fputcsv($f, []);
            fputcsv($f, ['Grand Total', number_format($data['totalLiters'], 2), number_format($data['totalAmount'], 2), $shifts->count()]);
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    /*
    |--------------------------------------------------------------------------
    | PROFIT & LOSS
    |--------------------------------------------------------------------------
    */
    private function getProfitLossData(Request $request): array
    {
        $range = $this->getReportRange($request);
        $from = $range['from'] . ' 00:00:00';
        $to = $range['to'] . ' 23:59:59';

        $sales = (float) EmployeeShift::whereBetween('created_at', [$from, $to])->sum('total_amount');
        $salesLiters = (float) EmployeeShift::whereBetween('created_at', [$from, $to])->sum('total_liters');
        $salesCount = EmployeeShift::whereBetween('created_at', [$from, $to])->count();

        $expenses = (float) Expense::whereBetween('expense_date', [$range['from'], $range['to']])->sum('amount');
        $expenseCount = Expense::whereBetween('expense_date', [$range['from'], $range['to']])->count();
        $ownerFuel = (float) OwnerFuelUsage::whereBetween('usage_datetime', [$from, $to])->sum('total_amount');
        $ownerFuelLiters = (float) OwnerFuelUsage::whereBetween('usage_datetime', [$from, $to])->sum('liters');
        $ownerFuelCount = OwnerFuelUsage::whereBetween('usage_datetime', [$from, $to])->count();

        $refillCogs = (float) TankRefill::whereBetween('received_datetime', [$from, $to])->sum('total_amount');
        $refillLiters = (float) TankRefill::whereBetween('received_datetime', [$from, $to])->sum('quantity_liters');

        $totalCosts = $expenses + $ownerFuel + $refillCogs;
        $grossProfit = $sales - ($expenses + $ownerFuel);
        $netProfit = $sales - $totalCosts;
        $profitMargin = $sales > 0 ? round(($grossProfit / $sales) * 100, 2) : 0;
        $expenseRatio = $sales > 0 ? round(($expenses / $sales) * 100, 2) : 0;
        $ownerFuelRatio = $sales > 0 ? round(($ownerFuel / $sales) * 100, 2) : 0;

        $expenseByType = Expense::whereBetween('expense_date', [$range['from'], $range['to']])
            ->selectRaw('expense_type, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('expense_type')
            ->orderByDesc('total')
            ->get();

        $salesByDay = EmployeeShift::whereBetween('created_at', [$from, $to])
            ->get()
            ->groupBy(fn ($s) => $s->created_at->format('Y-m-d'));

        $expensesByDay = Expense::whereBetween('expense_date', [$range['from'], $range['to']])
            ->get()
            ->groupBy(fn ($e) => Carbon::parse($e->expense_date)->format('Y-m-d'));

        $ownerFuelByDay = OwnerFuelUsage::whereBetween('usage_datetime', [$from, $to])
            ->get()
            ->groupBy(fn ($o) => Carbon::parse($o->usage_datetime)->format('Y-m-d'));

        $allDates = collect()
            ->merge($salesByDay->keys())
            ->merge($expensesByDay->keys())
            ->merge($ownerFuelByDay->keys())
            ->unique()
            ->sort()
            ->values();

        $dailyBreakdown = $allDates->map(function ($date) use ($salesByDay, $expensesByDay, $ownerFuelByDay) {
            $daySales = (float) ($salesByDay->get($date)?->sum('total_amount') ?? 0);
            $dayExpenses = (float) ($expensesByDay->get($date)?->sum('amount') ?? 0);
            $dayOwnerFuel = (float) ($ownerFuelByDay->get($date)?->sum('total_amount') ?? 0);
            $dayCosts = $dayExpenses + $dayOwnerFuel;

            return [
                'date' => $date,
                'label' => Carbon::parse($date)->format('d M Y'),
                'sales' => $daySales,
                'expenses' => $dayExpenses,
                'owner_fuel' => $dayOwnerFuel,
                'costs' => $dayCosts,
                'net' => $daySales - $dayCosts,
            ];
        });

        return array_merge($range, compact(
            'sales',
            'salesLiters',
            'salesCount',
            'expenses',
            'expenseCount',
            'ownerFuel',
            'ownerFuelLiters',
            'ownerFuelCount',
            'refillCogs',
            'refillLiters',
            'totalCosts',
            'grossProfit',
            'netProfit',
            'profitMargin',
            'expenseRatio',
            'ownerFuelRatio',
            'expenseByType',
            'dailyBreakdown'
        ));
    }

    public function profitLoss(Request $request)
    {
        return view('reports.profit_loss', $this->getProfitLossData($request));
    }

    public function profitLossPdf(Request $request)
    {
        $data = $this->getProfitLossData($request);
        $pdf = PDF::loadView('reports.pdf.profit_loss', $data);

        return $pdf->download('profit-loss-report.pdf');
    }

    public function profitLossCsv(Request $request)
    {
        $data = $this->getProfitLossData($request);
        $filename = 'profit-loss-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Profit & Loss Report']);
            fputcsv($f, ['Range', $data['from'] . ' to ' . $data['to']]);
            fputcsv($f, ['Filter', ucfirst(str_replace('-', ' ', $data['filter']))]);
            fputcsv($f, []);
            fputcsv($f, ['Summary']);
            fputcsv($f, ['Total Sales (PKR)', number_format($data['sales'], 2)]);
            fputcsv($f, ['Sales Liters', number_format($data['salesLiters'], 2)]);
            fputcsv($f, ['Sales Transactions', $data['salesCount']]);
            fputcsv($f, ['Total Expenses (PKR)', number_format($data['expenses'], 2)]);
            fputcsv($f, ['Owner Fuel Usage (PKR)', number_format($data['ownerFuel'], 2)]);
            fputcsv($f, ['Tank Refill COGS (PKR)', number_format($data['refillCogs'], 2)]);
            fputcsv($f, ['Gross Profit (PKR)', number_format($data['grossProfit'], 2)]);
            fputcsv($f, ['Total Costs incl. COGS (PKR)', number_format($data['totalCosts'], 2)]);
            fputcsv($f, ['Net Profit (PKR)', number_format($data['netProfit'], 2)]);
            fputcsv($f, ['Profit Margin %', $data['profitMargin']]);
            fputcsv($f, []);
            fputcsv($f, ['Expense Breakdown by Type']);
            fputcsv($f, ['Type', 'Amount (PKR)', 'Count']);

            foreach ($data['expenseByType'] as $row) {
                fputcsv($f, [$row->expense_type, number_format($row->total, 2), $row->count]);
            }

            fputcsv($f, []);
            fputcsv($f, ['Daily Breakdown']);
            fputcsv($f, ['Date', 'Sales', 'Expenses', 'Owner Fuel', 'Total Costs', 'Net Profit']);

            foreach ($data['dailyBreakdown'] as $day) {
                fputcsv($f, [
                    $day['label'],
                    number_format($day['sales'], 2),
                    number_format($day['expenses'], 2),
                    number_format($day['owner_fuel'], 2),
                    number_format($day['costs'], 2),
                    number_format($day['net'], 2),
                ]);
            }

            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    /*
    |--------------------------------------------------------------------------
    | STOCK REPORT
    |--------------------------------------------------------------------------
    */
    private function getStockData(): array
    {
        $tanks = Tank::with('product')->orderBy('tank_number')->get();

        $rows = $tanks->map(function ($tank) {
            $capacity = (float) $tank->capacity_liters;
            $stock = (float) $tank->current_stock_liters;
            $minimum = (float) $tank->minimum_level;
            $fillPercent = $capacity > 0 ? round(($stock / $capacity) * 100, 1) : 0;

            return [
                'tank_number' => $tank->tank_number,
                'product' => $tank->product->name ?? 'N/A',
                'capacity' => $capacity,
                'current_stock' => $stock,
                'minimum_level' => $minimum,
                'available' => max(0, $capacity - $stock),
                'fill_percent' => $fillPercent,
                'is_low' => $stock <= $minimum,
                'status' => $tank->status ?? 'active',
            ];
        });

        return [
            'tanks' => $rows,
            'totalCapacity' => $rows->sum('capacity'),
            'totalStock' => $rows->sum('current_stock'),
            'avgFillPercent' => $rows->avg('fill_percent') ?? 0,
            'tankCount' => $rows->count(),
            'lowStockCount' => $rows->where('is_low', true)->count(),
            'generatedAt' => now()->format('d M Y, h:i A'),
        ];
    }

    public function stock()
    {
        return view('reports.stock', $this->getStockData());
    }

    public function stockPdf()
    {
        $data = $this->getStockData();
        $pdf = PDF::loadView('reports.pdf.stock', $data);

        return $pdf->download('stock-report.pdf');
    }

    public function stockCsv()
    {
        $data = $this->getStockData();
        $filename = 'stock-report-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['Tank', 'Product', 'Capacity (L)', 'Current Stock (L)', 'Available (L)', 'Min Level (L)', 'Fill %', 'Status', 'Alert'];

        $callback = function () use ($data, $columns) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Tank Stock Report']);
            fputcsv($f, ['Generated', $data['generatedAt']]);
            fputcsv($f, ['Tanks', $data['tankCount']]);
            fputcsv($f, ['Total Capacity (L)', number_format($data['totalCapacity'], 2)]);
            fputcsv($f, ['Total Stock (L)', number_format($data['totalStock'], 2)]);
            fputcsv($f, ['Low Stock Alerts', $data['lowStockCount']]);
            fputcsv($f, []);
            fputcsv($f, $columns);

            foreach ($data['tanks'] as $t) {
                fputcsv($f, [
                    $t['tank_number'],
                    $t['product'],
                    number_format($t['capacity'], 2),
                    number_format($t['current_stock'], 2),
                    number_format($t['available'], 2),
                    number_format($t['minimum_level'], 2),
                    $t['fill_percent'] . '%',
                    $t['status'],
                    $t['is_low'] ? 'Low Stock' : 'OK',
                ]);
            }

            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    /*
    |--------------------------------------------------------------------------
    | EXPENSE REPORT
    |--------------------------------------------------------------------------
    */
    private function getExpensesRange(Request $request)
    {
        return $this->getReportRange($request);
    }

    private function getExpensesData(Request $request)
    {
        $range = $this->getExpensesRange($request);

        $query = Expense::query();

        if ($range['from'] && $range['to']) {
            $query->whereBetween('expense_date', [$range['from'], $range['to']]);
        }

        $expenses = $query->latest('expense_date')->get();
        $totalAmount = $expenses->sum('amount');
        $totalRecords = $expenses->count();

        $dailyTotals = $expenses
            ->groupBy(fn ($expense) => \Carbon\Carbon::parse($expense->expense_date)->format('Y-m-d'))
            ->map(function ($group, $date) {
                return [
                    'date' => $date,
                    'label' => \Carbon\Carbon::parse($date)->format('d M Y'),
                    'total_amount' => $group->sum('amount'),
                    'record_count' => $group->count(),
                ];
            })
            ->values();

        return array_merge($range, compact('expenses', 'totalAmount', 'totalRecords', 'dailyTotals'));
    }

    public function expenses(Request $request)
    {
        return view('reports.expenses', $this->getExpensesData($request));
    }

    public function expensesPdf(Request $request)
    {
        $data = $this->getExpensesData($request);
        $pdf = PDF::loadView('reports.pdf.expense', $data);

        return $pdf->download('expense-report.pdf');
    }

    public function expensesCsv(Request $request)
    {
        $data = $this->getExpensesData($request);
        $expenses = $data['expenses'];
        $dailyTotals = $data['dailyTotals'];
        $filename = 'expense-report-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['Type', 'Amount (PKR)', 'Date', 'Notes'];

        $callback = function () use ($expenses, $dailyTotals, $data, $columns) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Expense Report']);
            fputcsv($f, ['Range', $data['from'] . ' to ' . $data['to']]);
            fputcsv($f, ['Filter', ucfirst(str_replace('-', ' ', $data['filter']))]);
            fputcsv($f, ['Records', $expenses->count()]);
            fputcsv($f, ['Total Amount', number_format($data['totalAmount'], 2)]);
            fputcsv($f, []);
            fputcsv($f, $columns);

            foreach ($expenses as $e) {
                fputcsv($f, [
                    $e->expense_type,
                    number_format($e->amount, 2),
                    \Carbon\Carbon::parse($e->expense_date)->format('d-m-Y'),
                    $e->notes ?? '',
                ]);
            }

            fputcsv($f, []);
            fputcsv($f, ['Daily Totals']);
            fputcsv($f, ['Date', 'Amount (PKR)', 'Records']);

            foreach ($dailyTotals as $day) {
                fputcsv($f, [
                    $day['label'],
                    number_format($day['total_amount'], 2),
                    $day['record_count'],
                ]);
            }

            fputcsv($f, []);
            fputcsv($f, ['Grand Total', number_format($data['totalAmount'], 2), $expenses->count()]);
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    /*
    |--------------------------------------------------------------------------
    | VARIANCE REPORT
    |--------------------------------------------------------------------------
    */
    private function getVarianceData(): array
    {
        $tanks = Tank::with('product')->orderBy('tank_number')->get();
        $variances = collect();

        foreach ($tanks as $tank) {
            $system = (float) $tank->current_stock_liters;
            $dip = TankDipReading::where('tank_id', $tank->id)->latest('reading_datetime')->first();
            $physical = $dip ? (float) $dip->measured_liters : $system;
            $difference = $physical - $system;

            if (abs($difference) < 0.01) {
                $status = 'match';
                $statusLabel = 'Matched';
            } elseif ($difference > 0) {
                $status = 'over';
                $statusLabel = 'Overage';
            } else {
                $status = 'under';
                $statusLabel = 'Shortage';
            }

            $variances->push([
                'tank_number' => $tank->tank_number,
                'product' => $tank->product->name ?? 'N/A',
                'system' => $system,
                'physical' => $physical,
                'difference' => $difference,
                'status' => $status,
                'status_label' => $statusLabel,
                'dip_date' => $dip ? Carbon::parse($dip->reading_datetime)->format('d M Y, h:i A') : null,
                'has_dip' => (bool) $dip,
            ]);
        }

        return [
            'variances' => $variances,
            'totalVariance' => $variances->sum('difference'),
            'tanksWithVariance' => $variances->where('status', '!=', 'match')->count(),
            'matchedCount' => $variances->where('status', 'match')->count(),
            'tankCount' => $variances->count(),
            'generatedAt' => now()->format('d M Y, h:i A'),
        ];
    }

    public function variance()
    {
        return view('reports.variance', $this->getVarianceData());
    }

    public function variancePdf()
    {
        $data = $this->getVarianceData();
        $pdf = PDF::loadView('reports.pdf.variance', $data);

        return $pdf->download('variance-report.pdf');
    }

    public function varianceCsv()
    {
        $data = $this->getVarianceData();
        $filename = 'variance-report-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['Tank', 'Product', 'System (L)', 'Physical (L)', 'Difference (L)', 'Status', 'Last Dip Reading'];

        $callback = function () use ($data, $columns) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Tank Variance Report']);
            fputcsv($f, ['Generated', $data['generatedAt']]);
            fputcsv($f, ['Tanks', $data['tankCount']]);
            fputcsv($f, ['Matched', $data['matchedCount']]);
            fputcsv($f, ['With Variance', $data['tanksWithVariance']]);
            fputcsv($f, ['Total Variance (L)', number_format($data['totalVariance'], 2)]);
            fputcsv($f, []);
            fputcsv($f, $columns);

            foreach ($data['variances'] as $v) {
                fputcsv($f, [
                    $v['tank_number'],
                    $v['product'],
                    number_format($v['system'], 2),
                    number_format($v['physical'], 2),
                    number_format($v['difference'], 2),
                    $v['status_label'],
                    $v['dip_date'] ?? 'No dip reading',
                ]);
            }

            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }
}