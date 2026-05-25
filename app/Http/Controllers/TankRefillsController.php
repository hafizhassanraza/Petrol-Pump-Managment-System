<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Tank;
use App\Models\TankRefill;
use App\Services\StockService;
use App\Support\ReportRange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TankRefillsController extends Controller
{
    public function index(Request $request)
    {
        $range = ReportRange::fromRequest($request);
        $from = $range['from'] . ' 00:00:00';
        $to = $range['to'] . ' 23:59:59';

        $refills = TankRefill::with(['tank', 'product'])
            ->whereBetween('received_datetime', [$from, $to])
            ->latest('received_datetime')
            ->paginate(15)
            ->withQueryString();

        return view('tank_refills.index', array_merge($range, compact('refills')));
    }

    public function create()
    {
        return view('tank_refills.create', [
            'tanks' => Tank::with('product')->where('status', 1)->get(),
            'products' => Product::where('status', 1)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tank_id' => 'required|exists:tanks,id',
            'product_id' => 'required|exists:products,id',
            'quantity_liters' => 'required|numeric|min:0.1',
            'purchase_rate' => 'required|numeric|min:0.01',
            'invoice_no' => 'nullable|string|max:100',
        ]);

        $tank = Tank::findOrFail($request->tank_id);
        $quantity = (float) $request->quantity_liters;

        if ((int) $tank->product_id !== (int) $request->product_id) {
            return back()->withInput()->with('error', 'Selected product does not match tank product.');
        }

        if (! StockService::canIncrement($tank, $quantity)) {
            return back()->withInput()->with(
                'error',
                'Refill exceeds tank capacity. Available space: ' .
                number_format(max(0, $tank->capacity_liters - $tank->current_stock_liters), 2) . ' L'
            );
        }

        $totalAmount = round($quantity * (float) $request->purchase_rate, 2);

        try {
            DB::transaction(function () use ($request, $tank, $quantity, $totalAmount) {
                TankRefill::create([
                    'tank_id' => $tank->id,
                    'product_id' => $request->product_id,
                    'invoice_no' => $request->invoice_no,
                    'quantity_liters' => $quantity,
                    'purchase_rate' => $request->purchase_rate,
                    'total_amount' => $totalAmount,
                    'received_datetime' => now(),
                    'notes' => $request->notes,
                    'created_by' => Auth::id() ?? 1,
                ]);

                StockService::increment($tank, $quantity);
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('tank-refills.index')
            ->with('success', 'Tank refill recorded. Stock increased by ' . number_format($quantity, 2) . ' L');
    }
}
