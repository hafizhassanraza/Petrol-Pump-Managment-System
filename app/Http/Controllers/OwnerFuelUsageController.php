<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Nozzle;
use App\Models\OwnerFuelUsage;
use App\Services\ProductPriceService;
use App\Services\StockService;
use App\Support\ReportRange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OwnerFuelUsageController extends Controller
{
    public function index(Request $request)
    {
        $range = ReportRange::fromRequest($request);
        $from = $range['from'] . ' 00:00:00';
        $to = $range['to'] . ' 23:59:59';

        $usages = OwnerFuelUsage::with(['product', 'nozzle', 'employee'])
            ->whereBetween('usage_datetime', [$from, $to])
            ->latest('usage_datetime')
            ->paginate(15)
            ->withQueryString();

        return view('owner_fuel_usages.index', array_merge($range, compact('usages')));
    }

    public function create()
    {
        return view('owner_fuel_usages.create', [
            'nozzles' => Nozzle::where('status', 1)->with(['product', 'tank'])->get(),
            'employees' => Employee::where('status', 1)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nozzle_id' => 'required|exists:nozzles,id',
            'liters' => 'required|numeric|min:0.1',
            'person_name' => 'nullable|string|max:255',
            'vehicle_no' => 'nullable|string|max:50',
            'purpose' => 'nullable|string|max:255',
        ]);

        $nozzle = Nozzle::with(['product', 'tank'])->findOrFail($request->nozzle_id);

        if (! $nozzle->product) {
            return back()->withInput()->with('error', 'No product linked to this nozzle.');
        }

        $liters = (float) $request->liters;
        $pricePerLiter = ProductPriceService::getPricePerLiter(
            $nozzle->product_id,
            now()
        );

        if ($pricePerLiter === null) {
            return back()->withInput()->with('error', 'No selling price for this product.');
        }

        $total = round($liters * $pricePerLiter, 2);
        $tank = $nozzle->tank;

        if (! $tank) {
            return back()->withInput()->with('error', 'Tank not linked to nozzle.');
        }

        if (! StockService::canDecrement($tank, $liters)) {
            return back()->withInput()->with(
                'error',
                'Insufficient stock. Available: ' . number_format($tank->current_stock_liters, 2) . ' L'
            );
        }

        try {
            DB::transaction(function () use ($request, $nozzle, $liters, $pricePerLiter, $total, $tank) {
                OwnerFuelUsage::create([
                    'product_id' => $nozzle->product_id,
                    'nozzle_id' => $nozzle->id,
                    'employee_id' => $request->employee_id,
                    'vehicle_no' => $request->vehicle_no,
                    'person_name' => $request->person_name ?? 'Owner',
                    'purpose' => $request->purpose,
                    'liters' => $liters,
                    'price_per_liter' => $pricePerLiter,
                    'total_amount' => $total,
                    'usage_datetime' => now(),
                    'notes' => $request->notes,
                    'created_by' => Auth::id() ?? 1,
                ]);

                StockService::decrement($tank, $liters);

                $nozzle->update([
                    'current_meter_reading' => (float) $nozzle->current_meter_reading + $liters,
                ]);
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('owner-fuel-usages.index')
            ->with('success', 'Owner fuel usage recorded (PKR ' . number_format($total, 2) . ').');
    }
}
