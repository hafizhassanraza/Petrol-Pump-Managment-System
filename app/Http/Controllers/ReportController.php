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

        if ($request->from && $request->to) {
            $query->whereBetween('created_at', [$request->from, $request->to]);
        }

        $shifts = $query->latest()->get();

        return view('reports.daily_sales', compact('shifts'));
    }
    public function dailySalesPdf(Request $request)
    {
        $shifts = EmployeeShift::with('employee','nozzle')->get();

        $pdf = PDF::loadView('reports.pdf.daily_sales', compact('shifts'));

        return $pdf->download('daily-sales-report.pdf');
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
}