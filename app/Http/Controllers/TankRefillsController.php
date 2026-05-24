<?php

namespace App\Http\Controllers;

use App\Models\Tank;
use App\Models\Product;
use App\Models\TankRefill;
use Illuminate\Http\Request;

class TankRefillsController extends Controller
{
    public function index()
    {
        $refills = TankRefill::with([
            'tank',
            'product'
        ])->latest()->get();

        return view(
            'tank_refills.index',
            compact('refills')
        );
    }


    public function create()
    {
        return view('tank_refills.create', [

            'tanks' =>
                Tank::where('status', 1)->get(),

            'products' =>
                Product::where('status', 1)->get(),

        ]);
    }


    public function store(Request $request)
    {
        $request->validate([

            'tank_id' =>
                'required',

            'product_id' =>
                'required',

            'quantity_liters' =>
                'required|numeric|min:1',

            'purchase_rate' =>
                'required|numeric|min:1',

        ]);


        /*
        |--------------------------------------------------------------------------
        | TOTAL AMOUNT
        |--------------------------------------------------------------------------
        */

        $totalAmount =
            $request->quantity_liters
            * $request->purchase_rate;


        /*
        |--------------------------------------------------------------------------
        | CREATE REFILL
        |--------------------------------------------------------------------------
        */

        $refill = TankRefill::create([

            'tank_id' =>
                $request->tank_id,

            'product_id' =>
                $request->product_id,

            'invoice_no' =>
                $request->invoice_no,

            'quantity_liters' =>
                $request->quantity_liters,

            'purchase_rate' =>
                $request->purchase_rate,

            'total_amount' =>
                $totalAmount,

            'received_datetime' =>
                now(),

            'notes' =>
                $request->notes,

            'created_by' =>
                1,
        ]);


        /*
        |--------------------------------------------------------------------------
        | UPDATE TANK STOCK
        |--------------------------------------------------------------------------
        */

        $tank = Tank::find($request->tank_id);

        $tank->increment(
            'current_stock_liters',
            $request->quantity_liters
        );


        return redirect()
            ->route('tank-refills.index')
            ->with(
                'success',
                'Tank refill added successfully.'
            );
    }
}