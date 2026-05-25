<?php

namespace App\Http\Controllers;

use App\Models\OwnerFuelUsage;
use App\Models\Product;
use App\Models\Nozzle;
use Illuminate\Http\Request;

class OwnerFuelUsageController extends Controller
{
    public function index()
    {
        $usages = OwnerFuelUsage::with([
            'product',
            'nozzle'
        ])->latest()->get();

        return view('owner_fuel_usages.index', compact('usages'));
    }


    public function create()
    {
        return view('owner_fuel_usages.create', [
            'nozzles' => Nozzle::where('status', 1)->with('product')->get(),
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'nozzle_id' => 'required',
            'liters' => 'required|numeric|min:0.1',
        ]);


        // derive nozzle and product
        $nozzle = Nozzle::with(['product', 'tank'])->find($request->nozzle_id);

        if (!$nozzle || !$nozzle->product) {
            return back()->with('error', 'Selected nozzle or its product not found.');
        }

        $productId = $nozzle->product_id;

        $price = \App\Models\ProductPrice::where('product_id', $productId)
            ->latest('effective_from')
            ->first();

        if (!$price) {
            return back()->with('error', 'Price not found for product.');
        }

        $total = $request->liters * $price->price;


        /*
        |--------------------------------------------------------------------------
        | CREATE OWNER USAGE
        |--------------------------------------------------------------------------
        */

        OwnerFuelUsage::create([
            'product_id' => $productId,
            'nozzle_id' => $request->nozzle_id,
            'employee_id' => $request->employee_id,
            'vehicle_no' => $request->vehicle_no,
            'person_name' => $request->person_name,
            'purpose' => $request->purpose,
            'liters' => $request->liters,
            'price_per_liter' => $price->price,
            'total_amount' => $total,
            'usage_datetime' => now(),
            'notes' => $request->notes,
            'created_by' => 1,
        ]);


        /*
        |--------------------------------------------------------------------------
        | REDUCE TANK STOCK
        |--------------------------------------------------------------------------
        */

        // Reduce tank stock if available and sufficient
        if ($nozzle && $nozzle->tank) {
            $tank = $nozzle->tank;

            if ($tank->current_stock_liters < $request->liters) {
                return back()->with('error', 'Insufficient stock in tank for this nozzle.');
            }

            $tank->decrement('current_stock_liters', $request->liters);
        }


        return redirect()
            ->route('owner-fuel-usages.index')
            ->with(
                'success',
                'Owner fuel usage recorded.'
            );
    }
}