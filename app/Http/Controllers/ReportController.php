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
    public function dailySales(Request $request)
    {
        $query = EmployeeShift::with('employee','nozzle');

        // Determine date range based on filter
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
            // Default to today
            $from = now()->format('Y-m-d');
            $to = now()->format('Y-m-d');
        }

        if ($from && $to) {
            $query->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
        }

        $shifts = $query->latest()->get();

        // Calculate totals
        $totalAmount = $shifts->sum('total_amount');
        $totalLiters = $shifts->sum('total_liters');

        return view('reports.daily_sales', compact('shifts', 'totalAmount', 'totalLiters', 'from', 'to', 'filter'));
    }
    public function dailySalesPdf(Request $request)
    {
        $query = EmployeeShift::with('employee','nozzle');

        // Determine date range based on filter
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
            // Default to today
            $from = now()->format('Y-m-d');
            $to = now()->format('Y-m-d');
        }

        if ($from && $to) {
            $query->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
        }

        $shifts = $query->latest()->get();

        $pdf = PDF::loadView('reports.pdf.daily_sales', compact('shifts'));

        return $pdf->download('daily-sales-report.pdf');
    }

    public function dailySalesCsv(Request $request)
    {
        $query = EmployeeShift::with('employee','nozzle');

        // Determine date range based on filter
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
            // Default to today
            $from = now()->format('Y-m-d');
            $to = now()->format('Y-m-d');
        }

        if ($from && $to) {
            $query->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
        }

        $shifts = $query->latest()->get();

        $filename = 'daily-sales-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['Employee','Nozzle','Liters','Amount','Date'];

        $callback = function() use ($shifts, $columns) {
            $f = fopen('php://output', 'w');
            fputcsv($f, $columns);

            foreach ($shifts as $s) {
                fputcsv($f, [
                    $s->employee->name ?? '',
                    $s->nozzle->nozzle_number ?? '',
                    $s->total_liters,
                    $s->total_amount,
                    $s->created_at,
                ]);
            }

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