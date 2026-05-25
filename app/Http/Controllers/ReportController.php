<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmployeeShift;
use App\Models\Expense;
use App\Models\Tank;
use App\Models\OwnerFuelUsage;
use App\Models\TankDipReading;
use PDF;

class ReportController extends Controller
{
    public function dashboard()
    {
        return view('reports.dashboard');
    }

    /*
    |--------------------------------------------------------------------------
    | DAILY SALES
    |--------------------------------------------------------------------------
    */
    private function getDailySalesRange(Request $request)
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
            // Use custom range
        } else {
            $from = now()->format('Y-m-d');
            $to = now()->format('Y-m-d');
        }

        return compact('filter', 'from', 'to');
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
    | PROFIT LOSS
    |--------------------------------------------------------------------------
    */
    public function profitLoss()
    {
        $sales = EmployeeShift::sum('total_amount');
        $expenses = Expense::sum('amount');
        $ownerFuel = OwnerFuelUsage::sum('total_amount');

        $netProfit = $sales - ($expenses + $ownerFuel);

        return view('reports.profit_loss', compact(
            'sales',
            'expenses',
            'ownerFuel',
            'netProfit'
        ));
    }
    public function profitLossPdf()
    {
        $sales = EmployeeShift::sum('total_amount');
        $expenses = Expense::sum('amount');
        $ownerFuel = OwnerFuelUsage::sum('total_amount');

        $netProfit = $sales - ($expenses + $ownerFuel);

        $pdf = PDF::loadView('reports.pdf.profit_loss', compact(
            'sales','expenses','ownerFuel','netProfit'
        ));

        return $pdf->download('profit-loss-report.pdf');
    }

    public function profitLossCsv()
    {
        $sales = EmployeeShift::sum('total_amount');
        $expenses = Expense::sum('amount');
        $ownerFuel = OwnerFuelUsage::sum('total_amount');
        $netProfit = $sales - ($expenses + $ownerFuel);

        $filename = 'profit-loss.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($sales,$expenses,$ownerFuel,$netProfit) {
            $f = fopen('php://output', 'w');
            fputcsv($f, ['Sales', $sales]);
            fputcsv($f, ['Expenses', $expenses]);
            fputcsv($f, ['Owner Fuel', $ownerFuel]);
            fputcsv($f, ['Net Profit', $netProfit]);
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    /*
    |--------------------------------------------------------------------------
    | STOCK REPORT
    |--------------------------------------------------------------------------
    */
    public function stock()
    {
        $tanks = Tank::with('product')->get();
        return view('reports.stock', compact('tanks'));
    }
    public function stockPdf()
    {
        $tanks = Tank::with('product')->get();

        $pdf = PDF::loadView('reports.pdf.stock', compact('tanks'));

        return $pdf->download('stock-report.pdf');
    }

    public function stockCsv()
    {
        $tanks = Tank::with('product')->get();

        $filename = 'stock-report.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['Tank','Product','Capacity','Current Stock','Minimum Level'];

        $callback = function() use ($tanks, $columns) {
            $f = fopen('php://output', 'w');
            fputcsv($f, $columns);

            foreach ($tanks as $t) {
                fputcsv($f, [
                    $t->tank_number,
                    $t->product->name ?? '',
                    $t->capacity_liters,
                    $t->current_stock_liters,
                    $t->minimum_level,
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
    public function expenses()
    {
        $expenses = Expense::latest()->get();
        return view('reports.expenses', compact('expenses'));
    }
    public function expensesPdf()
    {
        $expenses = Expense::latest()->get();

        $pdf = PDF::loadView('reports.pdf.expenses', compact('expenses'));

        return $pdf->download('expense-report.pdf');
    }

    public function expensesCsv()
    {
        $expenses = Expense::latest()->get();

        $filename = 'expenses.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['ID','Type','Amount','Date','Notes'];

        $callback = function() use ($expenses, $columns) {
            $f = fopen('php://output', 'w');
            fputcsv($f, $columns);

            foreach ($expenses as $e) {
                fputcsv($f, [
                    $e->id,
                    $e->expense_type,
                    $e->amount,
                    $e->expense_date,
                    $e->notes,
                ]);
            }

            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    /*
    |--------------------------------------------------------------------------
    | VARIANCE REPORT
    |--------------------------------------------------------------------------
    */
    public function variance()
    {
        $tanks = Tank::with('product')->get();

        $variances = [];

        foreach ($tanks as $tank) {

            $system = $tank->current_stock_liters;

            $dip = TankDipReading::where('tank_id', $tank->id)
                ->latest()
                ->first();

            $physical = $dip->measured_liters ?? $system;

            $variances[] = [
                'tank' => $tank->tank_number,
                'system' => $system,
                'physical' => $physical,
                'difference' => $physical - $system,
            ];
        }

        return view('reports.variance', compact('variances'));
    }
    public function variancePdf()
    {
        $tanks = Tank::with('product')->get();

        $variances = [];

        foreach ($tanks as $tank) {

            $system = $tank->current_stock_liters;

            $dip = TankDipReading::where('tank_id',$tank->id)->latest()->first();

            $physical = $dip->measured_liters ?? $system;

            $variances[] = [
                'tank' => $tank->tank_number,
                'system' => $system,
                'physical' => $physical,
                'difference' => $physical - $system
            ];
        }

        $pdf = PDF::loadView('reports.pdf.variance', compact('variances'));

        return $pdf->download('variance-report.pdf');
    }

    public function varianceCsv()
    {
        $tanks = Tank::with('product')->get();

        $filename = 'variance.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['Tank','System','Physical','Difference'];

        $callback = function() use ($tanks, $columns) {
            $f = fopen('php://output', 'w');
            fputcsv($f, $columns);

            foreach ($tanks as $tank) {
                $system = $tank->current_stock_liters;
                $dip = TankDipReading::where('tank_id', $tank->id)->latest()->first();
                $physical = $dip->measured_liters ?? $system;
                fputcsv($f, [
                    $tank->tank_number,
                    $system,
                    $physical,
                    $physical - $system,
                ]);
            }

            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }
}