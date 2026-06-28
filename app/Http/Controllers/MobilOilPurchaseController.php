<?php

namespace App\Http\Controllers;

use App\Models\MobilOilProduct;
use App\Models\MobilOilPurchase;
use App\Services\MobilOilStockService;
use App\Support\ReportRange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MobilOilPurchaseController extends Controller
{
    public function index(Request $request)
    {
        $range = ReportRange::fromRequest($request);
        $from = $range['from'] . ' 00:00:00';
        $to = $range['to'] . ' 23:59:59';

        $purchases = MobilOilPurchase::with('product')
            ->whereBetween('received_datetime', [$from, $to])
            ->latest('received_datetime')
            ->paginate(15)
            ->withQueryString();

        return view('mobil_oil.purchases.index', array_merge($range, compact('purchases')));
    }

    public function create()
    {
        return view('mobil_oil.purchases.create', [
            'products' => MobilOilProduct::where('status', 1)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'mobil_oil_product_id' => 'required|exists:mobil_oil_products,id',
            'quantity' => 'required|numeric|min:0.01',
            'purchase_rate' => 'required|numeric|min:0.01',
            'invoice_no' => 'nullable|string|max:100',
            'received_datetime' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $product = MobilOilProduct::findOrFail($request->mobil_oil_product_id);
        $quantity = (float) $request->quantity;
        $totalAmount = round($quantity * (float) $request->purchase_rate, 2);

        try {
            DB::transaction(function () use ($request, $product, $quantity, $totalAmount) {
                MobilOilPurchase::create([
                    'mobil_oil_product_id' => $product->id,
                    'invoice_no' => $request->invoice_no,
                    'quantity' => $quantity,
                    'purchase_rate' => $request->purchase_rate,
                    'total_amount' => $totalAmount,
                    'received_datetime' => $request->received_datetime ?? now(),
                    'notes' => $request->notes,
                    'created_by' => Auth::id() ?? 1,
                ]);

                MobilOilStockService::increment($product, $quantity);
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('mobil-oil.purchases.index')
            ->with('success', 'Mobil Oil purchase recorded. Stock increased by ' . number_format($quantity, 2));
    }
}
