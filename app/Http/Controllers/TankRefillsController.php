<?php

namespace App\Http\Controllers;

use App\Models\Tank;
use App\Models\TankRefill;
use App\Services\StockService;
use App\Support\FuelProducts;
use App\Support\ReportRange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TankRefillsController extends Controller
{
    public function index(Request $request)
    {
        $range = ReportRange::fromRequest($request);
        $from = $range['from'].' 00:00:00';
        $to = $range['to'].' 23:59:59';

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
            'products' => FuelProducts::all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $tank = Tank::findOrFail($data['tank_id']);
        $quantity = (float) $data['quantity_liters'];

        if ($error = $this->validateTankProduct($tank, (int) $data['product_id'])) {
            return back()->withInput()->with('error', $error);
        }

        if (! StockService::canIncrement($tank, $quantity)) {
            return back()->withInput()->with(
                'error',
                'Refill exceeds tank capacity. Available space: '.
                number_format(max(0, $tank->capacity_liters - $tank->current_stock_liters), 2).' L'
            );
        }

        $totalAmount = round($quantity * (float) $data['purchase_rate'], 2);
        $stockBefore = (float) $tank->current_stock_liters;

        try {
            DB::transaction(function () use ($data, $tank, $quantity, $totalAmount, $stockBefore) {
                TankRefill::create([
                    'tank_id' => $tank->id,
                    'product_id' => $data['product_id'],
                    'invoice_no' => $data['invoice_no'] ?? null,
                    'quantity_liters' => $quantity,
                    'stock_before_liters' => $stockBefore,
                    'purchase_rate' => $data['purchase_rate'],
                    'total_amount' => $totalAmount,
                    'received_datetime' => now(),
                    'notes' => $data['notes'] ?? null,
                    'created_by' => Auth::id() ?? 1,
                ]);

                StockService::increment($tank, $quantity);
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('tank-refills.index')
            ->with('success', 'Tank refill recorded. Stock increased by '.number_format($quantity, 2).' L');
    }

    public function edit(TankRefill $tank_refill)
    {
        return view('tank_refills.edit', [
            'refill' => $tank_refill->load(['tank', 'product']),
            'tanks' => Tank::with('product')->where('status', 1)->get(),
            'products' => FuelProducts::all(),
        ]);
    }

    public function update(Request $request, TankRefill $tank_refill)
    {
        $data = $this->validated($request);

        $oldTank = Tank::findOrFail($tank_refill->tank_id);
        $newTank = Tank::findOrFail($data['tank_id']);
        $oldQty = (float) $tank_refill->quantity_liters;
        $newQty = (float) $data['quantity_liters'];

        if ($error = $this->validateTankProduct($newTank, (int) $data['product_id'])) {
            return back()->withInput()->with('error', $error);
        }

        try {
            DB::transaction(function () use ($data, $tank_refill, $oldTank, $newTank, $oldQty, $newQty) {
                $oldTank->refresh();
                $newTank->refresh();

                if (! StockService::canDecrement($oldTank, $oldQty)) {
                    throw new \RuntimeException(
                        'Cannot edit refill: current stock is too low to reverse the original quantity (need '.
                        number_format($oldQty, 2).' L on tank '.$oldTank->tank_number.').'
                    );
                }

                StockService::decrement($oldTank, $oldQty);
                $oldTank->refresh();
                $newTank->refresh();

                if (! StockService::canIncrement($newTank, $newQty)) {
                    throw new \RuntimeException(
                        'Updated refill exceeds tank capacity. Available space: '.
                        number_format(max(0, $newTank->capacity_liters - $newTank->current_stock_liters), 2).' L'
                    );
                }

                $stockBefore = (float) $newTank->current_stock_liters;
                StockService::increment($newTank, $newQty);

                $tank_refill->update([
                    'tank_id' => $newTank->id,
                    'product_id' => $data['product_id'],
                    'invoice_no' => $data['invoice_no'] ?? null,
                    'quantity_liters' => $newQty,
                    'stock_before_liters' => $stockBefore,
                    'purchase_rate' => $data['purchase_rate'],
                    'total_amount' => round($newQty * (float) $data['purchase_rate'], 2),
                    'notes' => $data['notes'] ?? null,
                ]);
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('tank-refills.index')
            ->with('success', 'Tank refill updated. Stock adjusted to '.number_format($newQty, 2).' L');
    }

    public function revert(TankRefill $tank_refill)
    {
        $tank = Tank::findOrFail($tank_refill->tank_id);
        $quantity = (float) $tank_refill->quantity_liters;

        try {
            DB::transaction(function () use ($tank_refill, $tank, $quantity) {
                $tank->refresh();

                if (! StockService::canDecrement($tank, $quantity)) {
                    throw new \RuntimeException(
                        'Cannot revert refill: current stock is too low to remove '.
                        number_format($quantity, 2).' L from tank '.$tank->tank_number.'.'
                    );
                }

                StockService::decrement($tank, $quantity);
                $tank_refill->delete();
            });
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('tank-refills.index')
            ->with('success', 'Tank refill reverted. Stock reduced by '.number_format($quantity, 2).' L');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'tank_id' => 'required|exists:tanks,id',
            'product_id' => ['required', Rule::in(FuelProducts::all()->pluck('id')->all())],
            'quantity_liters' => 'required|numeric|min:0.1',
            'purchase_rate' => 'required|numeric|min:0.01',
            'invoice_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);
    }

    private function validateTankProduct(Tank $tank, int $productId): ?string
    {
        if ((int) $tank->product_id !== $productId) {
            return 'Selected product does not match tank product.';
        }

        return null;
    }
}
