<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\MobilOilProduct;
use App\Models\MobilOilSale;
use App\Services\MobilOilPriceService;
use App\Services\MobilOilStockService;
use App\Support\ReportRange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MobilOilSaleController extends Controller
{
    public function index(Request $request)
    {
        $range = ReportRange::fromRequest($request);
        $fromAt = $range['fromAt'];
        $toAt = $range['toAt'];

        $sales = MobilOilSale::with(['product', 'employee'])
            ->whereBetween('sold_datetime', [$fromAt, $toAt])
            ->latest('sold_datetime')
            ->paginate(15)
            ->withQueryString();

        $totalAmount = (float) MobilOilSale::whereBetween('sold_datetime', [$fromAt, $toAt])->sum('total_amount');

        return view('mobil_oil.sales.index', array_merge($range, compact('sales', 'totalAmount')));
    }

    public function create()
    {
        return view('mobil_oil.sales.create', [
            'products' => MobilOilProduct::where('status', 1)->with('latestPrice')->orderBy('name')->get(),
            'employees' => Employee::where('status', 1)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'mobil_oil_product_id' => 'required|exists:mobil_oil_products,id',
            'employee_id' => 'nullable|exists:employees,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'nullable|numeric|min:0.01',
            'payment_method' => 'required|in:cash,online',
            'sold_datetime' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $product = MobilOilProduct::findOrFail($request->mobil_oil_product_id);
        $quantity = (float) $request->quantity;
        $soldAt = $request->sold_datetime ? \Carbon\Carbon::parse($request->sold_datetime) : now();

        $unitPrice = $request->filled('unit_price')
            ? (float) $request->unit_price
            : MobilOilPriceService::getUnitPrice($product->id, $soldAt);

        if (! $unitPrice) {
            return back()->withInput()->with('error', 'No selling price set for this product. Set a price first.');
        }

        if (! MobilOilStockService::canDecrement($product, $quantity)) {
            return back()->withInput()->with(
                'error',
                'Insufficient stock. Available: ' . number_format((float) $product->current_stock_qty, 2) . ' ' . $product->unit
            );
        }

        $totalAmount = round($quantity * $unitPrice, 2);

        try {
            DB::transaction(function () use ($request, $product, $quantity, $unitPrice, $totalAmount, $soldAt) {
                MobilOilSale::create([
                    'mobil_oil_product_id' => $product->id,
                    'employee_id' => $request->employee_id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_amount' => $totalAmount,
                    'payment_method' => $request->payment_method,
                    'sold_datetime' => $soldAt,
                    'notes' => $request->notes,
                    'created_by' => Auth::id() ?? 1,
                ]);

                MobilOilStockService::decrement($product, $quantity);
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('mobil-oil.sales.index')
            ->with('success', 'Mobil Oil sale recorded. Amount: PKR ' . money($totalAmount));
    }
}
