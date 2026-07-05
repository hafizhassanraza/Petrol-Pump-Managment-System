<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Nozzle;
use App\Models\OwnerFuelUsage;
use App\Services\ProductPriceService;
use App\Services\StockService;
use App\Support\ReportRange;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OwnerFuelUsageController extends Controller
{
    public function index(Request $request)
    {
        $range = ReportRange::fromRequest($request);

        $usages = OwnerFuelUsage::with(['product', 'nozzle', 'employee'])
            ->whereBetween('usage_datetime', [$range['fromAt'], $range['toAt']])
            ->latest('usage_datetime')
            ->paginate(15)
            ->withQueryString();

        return view('owner_fuel_usages.index', array_merge($range, compact('usages')));
    }

    public function create()
    {
        return view('owner_fuel_usages.create', [
            'nozzles' => Nozzle::where('status', 1)->with(['product', 'tank'])->get(),
            'employees' => Employee::where('status', 1)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nozzle_id' => 'required|exists:nozzles,id',
            'employee_id' => 'nullable|exists:employees,id',
            'liters' => 'required|numeric|min:0.1',
            'person_name' => 'nullable|string|max:255',
            'vehicle_no' => 'nullable|string|max:50',
            'purpose' => 'nullable|string|max:255',
            'usage_datetime' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $nozzle = Nozzle::with(['product', 'tank'])->findOrFail($request->nozzle_id);
        $usageAt = $request->filled('usage_datetime') ? Carbon::parse($request->usage_datetime) : now();

        try {
            $metrics = $this->calculateUsageMetrics($nozzle, (float) $request->liters, $usageAt);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        try {
            DB::transaction(function () use ($request, $nozzle, $metrics, $usageAt) {
                OwnerFuelUsage::create([
                    'product_id' => $nozzle->product_id,
                    'nozzle_id' => $nozzle->id,
                    'employee_id' => $request->employee_id,
                    'vehicle_no' => $request->vehicle_no,
                    'person_name' => $request->person_name ?? 'Owner',
                    'purpose' => $request->purpose,
                    'liters' => $metrics['liters'],
                    'price_per_liter' => $metrics['price_per_liter'],
                    'total_amount' => $metrics['total_amount'],
                    'usage_datetime' => $usageAt,
                    'notes' => $request->notes,
                    'created_by' => Auth::id() ?? 1,
                ]);

                $this->applyUsageEffects($nozzle, $metrics['liters']);
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('owner-fuel-usages.index')
            ->with('success', 'Owner fuel usage recorded (PKR ' . number_format($metrics['total_amount'], 2) . ').');
    }

    public function edit(OwnerFuelUsage $owner_fuel_usage)
    {
        $usage = $owner_fuel_usage->load(['product', 'nozzle.tank', 'employee']);

        return view('owner_fuel_usages.edit', [
            'usage' => $usage,
            'nozzles' => Nozzle::where('status', 1)->with(['product', 'tank'])->get(),
            'employees' => Employee::where('status', 1)->orderBy('name')->get(),
            'pricePerLiter' => ProductPriceService::getPricePerLiter(
                $usage->product_id,
                $usage->usage_datetime ?? now()
            ),
        ]);
    }

    public function update(Request $request, OwnerFuelUsage $owner_fuel_usage)
    {
        $request->validate([
            'nozzle_id' => 'required|exists:nozzles,id',
            'employee_id' => 'nullable|exists:employees,id',
            'liters' => 'required|numeric|min:0.1',
            'person_name' => 'nullable|string|max:255',
            'vehicle_no' => 'nullable|string|max:50',
            'purpose' => 'nullable|string|max:255',
            'usage_datetime' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $usage = $owner_fuel_usage->load(['nozzle.tank']);
        $newNozzle = Nozzle::with(['product', 'tank'])->findOrFail($request->nozzle_id);
        $usageAt = $request->filled('usage_datetime')
            ? Carbon::parse($request->usage_datetime)
            : ($usage->usage_datetime ?? now());

        try {
            $metrics = $this->calculateUsageMetrics($newNozzle, (float) $request->liters, $usageAt);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        try {
            DB::transaction(function () use ($request, $usage, $newNozzle, $metrics, $usageAt) {
                $this->revertUsageEffects($usage);

                $usage->refresh();
                $newNozzle->refresh();

                if (! StockService::canDecrement($newNozzle->tank, $metrics['liters'])) {
                    throw new RuntimeException(
                        'Insufficient stock after recalculation. Available: ' .
                        number_format($newNozzle->tank->current_stock_liters, 2) . ' L'
                    );
                }

                $usage->update([
                    'product_id' => $newNozzle->product_id,
                    'nozzle_id' => $newNozzle->id,
                    'employee_id' => $request->employee_id,
                    'vehicle_no' => $request->vehicle_no,
                    'person_name' => $request->person_name ?? 'Owner',
                    'purpose' => $request->purpose,
                    'liters' => $metrics['liters'],
                    'price_per_liter' => $metrics['price_per_liter'],
                    'total_amount' => $metrics['total_amount'],
                    'usage_datetime' => $usageAt,
                    'notes' => $request->notes,
                ]);

                $this->applyUsageEffects($newNozzle, $metrics['liters']);
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('owner-fuel-usages.index')
            ->with('success', 'Owner fuel usage updated (PKR ' . number_format($metrics['total_amount'], 2) . ').');
    }

    /**
     * @return array{liters: float, price_per_liter: float, total_amount: float}
     */
    private function calculateUsageMetrics(Nozzle $nozzle, float $liters, Carbon $usageAt): array
    {
        if (! $nozzle->product) {
            throw new RuntimeException('No product linked to this nozzle.');
        }

        if (! $nozzle->tank) {
            throw new RuntimeException('Tank not linked to nozzle.');
        }

        $pricePerLiter = ProductPriceService::getPricePerLiter($nozzle->product_id, $usageAt);

        if ($pricePerLiter === null) {
            throw new RuntimeException('No selling price for this product.');
        }

        if (! StockService::canDecrement($nozzle->tank, $liters)) {
            throw new RuntimeException(
                'Insufficient stock. Available: ' . number_format($nozzle->tank->current_stock_liters, 2) . ' L'
            );
        }

        return [
            'liters' => $liters,
            'price_per_liter' => $pricePerLiter,
            'total_amount' => round($liters * $pricePerLiter, 2),
        ];
    }

    private function applyUsageEffects(Nozzle $nozzle, float $liters): void
    {
        StockService::decrement($nozzle->tank, $liters);

        $nozzle->update([
            'current_meter_reading' => (float) $nozzle->current_meter_reading + $liters,
        ]);
    }

    private function revertUsageEffects(OwnerFuelUsage $usage): void
    {
        $nozzle = $usage->nozzle()->with('tank')->first();
        $tank = $nozzle?->tank;
        $oldLiters = (float) $usage->liters;

        if (! $nozzle || ! $tank) {
            throw new RuntimeException('Original nozzle or tank no longer available.');
        }

        StockService::increment($tank, $oldLiters);

        $currentMeter = (float) $nozzle->fresh()->current_meter_reading;

        if ($currentMeter < $oldLiters) {
            throw new RuntimeException(
                'Nozzle meter is too low to safely reverse this usage. Cannot recalculate.'
            );
        }

        $nozzle->update([
            'current_meter_reading' => $currentMeter - $oldLiters,
        ]);
    }
}
