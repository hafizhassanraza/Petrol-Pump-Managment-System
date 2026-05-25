<?php

namespace App\Http\Controllers;

use App\Models\Tank;
use App\Models\TankDipReading;
use App\Services\StockService;
use App\Support\ReportRange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TankDipReadingController extends Controller
{
    public function index(Request $request)
    {
        $range = ReportRange::fromRequest($request);
        $from = $range['from'] . ' 00:00:00';
        $to = $range['to'] . ' 23:59:59';

        $readings = TankDipReading::with('tank.product')
            ->whereBetween('reading_datetime', [$from, $to])
            ->latest('reading_datetime')
            ->paginate(15)
            ->withQueryString();

        return view('tank_dip_readings.index', array_merge($range, compact('readings')));
    }

    public function create()
    {
        return view('tank_dip_readings.create', [
            'tanks' => Tank::with('product')->where('status', 1)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tank_id' => 'required|exists:tanks,id',
            'measured_liters' => 'required|numeric|min:0',
            'reconcile_stock' => 'nullable|boolean',
        ]);

        $tank = Tank::findOrFail($request->tank_id);
        $systemStock = (float) $tank->current_stock_liters;
        $physicalStock = (float) $request->measured_liters;
        $difference = round($physicalStock - $systemStock, 2);
        $reconcile = $request->boolean('reconcile_stock');

        try {
            DB::transaction(function () use ($request, $tank, $systemStock, $physicalStock, $difference, $reconcile) {
                TankDipReading::create([
                    'tank_id' => $tank->id,
                    'reading_datetime' => now(),
                    'measured_liters' => $physicalStock,
                    'system_stock_liters' => $systemStock,
                    'difference_liters' => $difference,
                    'stock_reconciled' => $reconcile,
                    'notes' => $request->notes,
                    'created_by' => Auth::id() ?? 1,
                ]);

                if ($reconcile) {
                    StockService::reconcile($tank, $physicalStock);
                }
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $msg = 'Dip reading saved. Variance: ' . ($difference >= 0 ? '+' : '') . number_format($difference, 2) . ' L';
        if ($reconcile) {
            $msg .= ' — system stock updated to match physical reading.';
        }

        return redirect()->route('tank-dip-readings.index')->with('success', $msg);
    }
}
