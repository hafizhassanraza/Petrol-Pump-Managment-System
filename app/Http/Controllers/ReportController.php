<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeShift;
use App\Models\Expense;
use App\Models\MobilOilProduct;
use App\Models\MobilOilPurchase;
use App\Models\MobilOilSale;
use App\Models\Tank;
use App\Models\OwnerFuelUsage;
use App\Models\TankDipReading;
use App\Models\TankRefill;
use App\Support\ReportRange;
use Carbon\Carbon;
use PDF;

class ReportController extends Controller
{
    public function dashboard()
    {
        return view('reports.dashboard');
    }

    private function getReportRange(Request $request): array
    {
        return ReportRange::fromRequest($request);
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
            $query->whereBetween('assigned_date', [$range['from'], $range['to']]);
        }

        $shifts = $query->latest()->get();
        $totalAmount = $shifts->sum('total_amount');
        $totalLiters = $shifts->sum('total_liters');

        $dailyTotals = $shifts
            ->groupBy(fn ($shift) => Carbon::parse($shift->assigned_date)->format('Y-m-d'))
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
        $from = $range['from'];
        $to = $range['to'];
        $fromAt = $range['fromAt'];
        $toAt = $range['toAt'];

        $fuelSales = (float) EmployeeShift::whereBetween('assigned_date', [$from, $to])->sum('total_amount');
        $salesLiters = (float) EmployeeShift::whereBetween('assigned_date', [$from, $to])->sum('total_liters');
        $salesCount = EmployeeShift::whereBetween('assigned_date', [$from, $to])->count();

        $mobilOilSales = (float) MobilOilSale::whereBetween('sold_datetime', [$fromAt, $toAt])->sum('total_amount');
        $mobilOilSalesQty = (float) MobilOilSale::whereBetween('sold_datetime', [$fromAt, $toAt])->sum('quantity');
        $mobilOilSalesCount = MobilOilSale::whereBetween('sold_datetime', [$fromAt, $toAt])->count();

        $sales = $fuelSales + $mobilOilSales;

        $expenses = (float) Expense::whereBetween('expense_date', [$from, $to])->sum('amount');
        $expenseCount = Expense::whereBetween('expense_date', [$from, $to])->count();
        $ownerFuel = (float) OwnerFuelUsage::whereBetween('usage_datetime', [$fromAt, $toAt])->sum('total_amount');
        $ownerFuelLiters = (float) OwnerFuelUsage::whereBetween('usage_datetime', [$fromAt, $toAt])->sum('liters');
        $ownerFuelCount = OwnerFuelUsage::whereBetween('usage_datetime', [$fromAt, $toAt])->count();

        $refillCogs = (float) TankRefill::whereBetween('received_datetime', [$fromAt, $toAt])->sum('total_amount');
        $refillLiters = (float) TankRefill::whereBetween('received_datetime', [$fromAt, $toAt])->sum('quantity_liters');

        $mobilOilCogs = (float) MobilOilPurchase::whereBetween('received_datetime', [$fromAt, $toAt])->sum('total_amount');
        $mobilOilPurchaseQty = (float) MobilOilPurchase::whereBetween('received_datetime', [$fromAt, $toAt])->sum('quantity');

        $totalCosts = $expenses + $ownerFuel + $refillCogs + $mobilOilCogs;
        $grossProfit = $sales - ($expenses + $ownerFuel);
        $netProfit = $sales - $totalCosts;
        $profitMargin = $sales > 0 ? round(($grossProfit / $sales) * 100, 2) : 0;
        $expenseRatio = $sales > 0 ? round(($expenses / $sales) * 100, 2) : 0;
        $ownerFuelRatio = $sales > 0 ? round(($ownerFuel / $sales) * 100, 2) : 0;

        $expenseByType = Expense::whereBetween('expense_date', [$from, $to])
            ->selectRaw('expense_type, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('expense_type')
            ->orderByDesc('total')
            ->get();

        $salesByDay = EmployeeShift::whereBetween('assigned_date', [$from, $to])
            ->get()
            ->groupBy(fn ($s) => Carbon::parse($s->assigned_date)->format('Y-m-d'));

        $expensesByDay = Expense::whereBetween('expense_date', [$from, $to])
            ->get()
            ->groupBy(fn ($e) => Carbon::parse($e->expense_date)->format('Y-m-d'));

        $ownerFuelByDay = OwnerFuelUsage::whereBetween('usage_datetime', [$fromAt, $toAt])
            ->get()
            ->groupBy(fn ($o) => Carbon::parse($o->usage_datetime)->format('Y-m-d'));

        $mobilOilSalesByDay = MobilOilSale::whereBetween('sold_datetime', [$fromAt, $toAt])
            ->get()
            ->groupBy(fn ($s) => Carbon::parse($s->sold_datetime)->format('Y-m-d'));

        $allDates = collect()
            ->merge($salesByDay->keys())
            ->merge($expensesByDay->keys())
            ->merge($ownerFuelByDay->keys())
            ->merge($mobilOilSalesByDay->keys())
            ->unique()
            ->sort()
            ->values();

        $dailyBreakdown = $allDates->map(function ($date) use ($salesByDay, $expensesByDay, $ownerFuelByDay, $mobilOilSalesByDay) {
            $dayFuelSales = (float) ($salesByDay->get($date)?->sum('total_amount') ?? 0);
            $dayMobilOilSales = (float) ($mobilOilSalesByDay->get($date)?->sum('total_amount') ?? 0);
            $daySales = $dayFuelSales + $dayMobilOilSales;
            $dayExpenses = (float) ($expensesByDay->get($date)?->sum('amount') ?? 0);
            $dayOwnerFuel = (float) ($ownerFuelByDay->get($date)?->sum('total_amount') ?? 0);
            $dayCosts = $dayExpenses + $dayOwnerFuel;

            return [
                'date' => $date,
                'label' => Carbon::parse($date)->format('d M Y'),
                'fuel_sales' => $dayFuelSales,
                'mobil_oil_sales' => $dayMobilOilSales,
                'sales' => $daySales,
                'expenses' => $dayExpenses,
                'owner_fuel' => $dayOwnerFuel,
                'costs' => $dayCosts,
                'net' => $daySales - $dayCosts,
            ];
        });

        return array_merge($range, compact(
            'sales',
            'fuelSales',
            'mobilOilSales',
            'mobilOilSalesQty',
            'mobilOilSalesCount',
            'salesLiters',
            'salesCount',
            'expenses',
            'expenseCount',
            'ownerFuel',
            'ownerFuelLiters',
            'ownerFuelCount',
            'refillCogs',
            'refillLiters',
            'mobilOilCogs',
            'mobilOilPurchaseQty',
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
            fputcsv($f, ['Fuel Sales (PKR)', number_format($data['fuelSales'], 2)]);
            fputcsv($f, ['Mobil Oil Sales (PKR)', number_format($data['mobilOilSales'], 2)]);
            fputcsv($f, ['Sales Liters', number_format($data['salesLiters'], 2)]);
            fputcsv($f, ['Sales Transactions', $data['salesCount']]);
            fputcsv($f, ['Total Expenses (PKR)', number_format($data['expenses'], 2)]);
            fputcsv($f, ['Owner Fuel Usage (PKR)', number_format($data['ownerFuel'], 2)]);
            fputcsv($f, ['Tank Refill COGS (PKR)', number_format($data['refillCogs'], 2)]);
            fputcsv($f, ['Mobil Oil Purchase COGS (PKR)', number_format($data['mobilOilCogs'], 2)]);
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
            fputcsv($f, ['Date', 'Total Sales', 'Fuel Sales', 'Mobil Oil Sales', 'Expenses', 'Owner Fuel', 'Total Costs', 'Net Profit']);

            foreach ($data['dailyBreakdown'] as $day) {
                fputcsv($f, [
                    $day['label'],
                    number_format($day['sales'], 2),
                    number_format($day['fuel_sales'], 2),
                    number_format($day['mobil_oil_sales'], 2),
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
            'mobilOilProducts' => $this->getMobilOilStockRows(),
            'generatedAt' => now()->format('d M Y, h:i A'),
        ];
    }

    private function getMobilOilStockRows()
    {
        return MobilOilProduct::orderBy('name')->get()->map(function ($product) {
            $stock = (float) $product->current_stock_qty;
            $minimum = (float) $product->minimum_level;

            return [
                'name' => $product->name,
                'sku' => $product->sku,
                'unit' => $product->unit,
                'current_stock' => $stock,
                'minimum_level' => $minimum,
                'is_low' => $stock <= $minimum,
                'status' => $product->status ? 'Active' : 'Inactive',
            ];
        });
    }

    public function stock()
    {
        $data = $this->getStockData();
        $data['mobilOilLowCount'] = collect($data['mobilOilProducts'])->where('is_low', true)->count();
        $data['mobilOilProductCount'] = collect($data['mobilOilProducts'])->count();

        return view('reports.stock', $data);
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

    /*
    |--------------------------------------------------------------------------
    | MOBIL OIL SALES REPORT
    |--------------------------------------------------------------------------
    */
    private function getMobilOilSalesData(Request $request): array
    {
        $range = $this->getReportRange($request);
        $from = $range['from'] . ' 00:00:00';
        $to = $range['to'] . ' 23:59:59';

        $sales = MobilOilSale::with(['product', 'employee'])
            ->whereBetween('sold_datetime', [$from, $to])
            ->latest('sold_datetime')
            ->get();

        $totalAmount = (float) $sales->sum('total_amount');
        $totalQty = (float) $sales->sum('quantity');

        $dailyTotals = $sales
            ->groupBy(fn ($sale) => Carbon::parse($sale->sold_datetime)->format('Y-m-d'))
            ->map(function ($group, $date) {
                return [
                    'date' => $date,
                    'label' => Carbon::parse($date)->format('d M Y'),
                    'total_amount' => $group->sum('total_amount'),
                    'total_qty' => $group->sum('quantity'),
                    'record_count' => $group->count(),
                ];
            })
            ->values();

        $byProduct = $sales
            ->groupBy('mobil_oil_product_id')
            ->map(function ($group) {
                $product = $group->first()->product;

                return [
                    'name' => $product->name ?? 'Unknown',
                    'unit' => $product->unit ?? '',
                    'total_amount' => $group->sum('total_amount'),
                    'total_qty' => $group->sum('quantity'),
                    'record_count' => $group->count(),
                ];
            })
            ->sortByDesc('total_amount')
            ->values();

        return array_merge($range, compact('sales', 'totalAmount', 'totalQty', 'dailyTotals', 'byProduct'));
    }

    public function mobilOilSales(Request $request)
    {
        return view('reports.mobil_oil_sales', $this->getMobilOilSalesData($request));
    }

    public function mobilOilSalesPdf(Request $request)
    {
        $data = $this->getMobilOilSalesData($request);
        $pdf = PDF::loadView('reports.pdf.mobil_oil_sales', $data);

        return $pdf->download('mobil-oil-sales-report.pdf');
    }

    public function mobilOilSalesCsv(Request $request)
    {
        $data = $this->getMobilOilSalesData($request);
        $filename = 'mobil-oil-sales-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Mobil Oil Sales Report']);
            fputcsv($f, ['Range', $data['from'] . ' to ' . $data['to']]);
            fputcsv($f, ['Total Sales (PKR)', number_format($data['totalAmount'], 2)]);
            fputcsv($f, ['Total Quantity', number_format($data['totalQty'], 2)]);
            fputcsv($f, []);
            fputcsv($f, ['Product', 'Quantity', 'Amount (PKR)', 'Payment', 'Employee', 'Sold At']);

            foreach ($data['sales'] as $s) {
                fputcsv($f, [
                    $s->product->name ?? '',
                    number_format($s->quantity, 2) . ' ' . ($s->product->unit ?? ''),
                    number_format($s->total_amount, 2),
                    ucfirst($s->payment_method),
                    $s->employee->name ?? '',
                    $s->sold_datetime?->format('d-m-Y H:i') ?? '',
                ]);
            }

            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    /*
    |--------------------------------------------------------------------------
    | ATTENDANCE REPORT
    |--------------------------------------------------------------------------
    */
    private function getAttendanceData(Request $request): array
    {
        $range = $this->getReportRange($request);

        $attendances = EmployeeAttendance::with('employee')
            ->whereBetween('attendance_date', [$range['from'], $range['to']])
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->get();

        $statusCounts = collect(EmployeeAttendance::STATUSES)
            ->mapWithKeys(fn ($status) => [
                $status => $attendances->where('status', $status)->count(),
            ]);

        $employeeSummaries = $attendances
            ->groupBy('employee_id')
            ->map(function ($group) {
                $employee = $group->first()->employee;

                return [
                    'employee_code' => $employee->employee_code ?? '—',
                    'name' => $employee->name ?? 'Unknown',
                    'present' => $group->where('status', 'present')->count(),
                    'absent' => $group->where('status', 'absent')->count(),
                    'late' => $group->where('status', 'late')->count(),
                    'half_day' => $group->where('status', 'half_day')->count(),
                    'on_leave' => $group->where('status', 'on_leave')->count(),
                    'total' => $group->count(),
                ];
            })
            ->sortBy('name')
            ->values();

        $dailyTotals = $attendances
            ->groupBy(fn ($row) => Carbon::parse($row->attendance_date)->format('Y-m-d'))
            ->map(function ($group, $date) {
                return [
                    'date' => $date,
                    'label' => Carbon::parse($date)->format('d M Y'),
                    'present' => $group->where('status', 'present')->count(),
                    'absent' => $group->where('status', 'absent')->count(),
                    'late' => $group->where('status', 'late')->count(),
                    'half_day' => $group->where('status', 'half_day')->count(),
                    'on_leave' => $group->where('status', 'on_leave')->count(),
                    'record_count' => $group->count(),
                ];
            })
            ->values();

        return array_merge($range, [
            'attendances' => $attendances,
            'totalRecords' => $attendances->count(),
            'statusCounts' => $statusCounts,
            'employeeSummaries' => $employeeSummaries,
            'dailyTotals' => $dailyTotals,
        ]);
    }

    public function attendance(Request $request)
    {
        return view('reports.attendance', $this->getAttendanceData($request));
    }

    public function attendancePdf(Request $request)
    {
        $data = $this->getAttendanceData($request);
        $pdf = PDF::loadView('reports.pdf.attendance', $data);

        return $pdf->download('attendance-report.pdf');
    }

    public function attendanceCsv(Request $request)
    {
        $data = $this->getAttendanceData($request);
        $attendances = $data['attendances'];
        $filename = 'attendance-report-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($attendances, $data) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Employee Attendance Report']);
            fputcsv($f, ['Range', $data['from'] . ' to ' . $data['to']]);
            fputcsv($f, ['Filter', ucfirst(str_replace('-', ' ', $data['filter']))]);
            fputcsv($f, ['Records', $data['totalRecords']]);
            fputcsv($f, []);
            fputcsv($f, ['Date', 'Employee', 'Code', 'Status', 'Check In', 'Check Out', 'Hours', 'Notes']);

            foreach ($attendances as $a) {
                fputcsv($f, [
                    $a->attendance_date->format('d-m-Y'),
                    $a->employee->name ?? '',
                    $a->employee->employee_code ?? '',
                    $a->status_label,
                    $a->check_in ? Carbon::parse($a->check_in)->format('H:i') : '',
                    $a->check_out ? Carbon::parse($a->check_out)->format('H:i') : '',
                    $a->worked_hours !== null ? number_format($a->worked_hours, 2) : '',
                    $a->notes ?? '',
                ]);
            }

            fputcsv($f, []);
            fputcsv($f, ['Employee Summary']);
            fputcsv($f, ['Name', 'Code', 'Present', 'Absent', 'Late', 'Half Day', 'On Leave', 'Total']);

            foreach ($data['employeeSummaries'] as $row) {
                fputcsv($f, [
                    $row['name'],
                    $row['employee_code'],
                    $row['present'],
                    $row['absent'],
                    $row['late'],
                    $row['half_day'],
                    $row['on_leave'],
                    $row['total'],
                ]);
            }

            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }
}