<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Tank;
use Illuminate\Http\Request;

class TankController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | DISPLAY TANKS
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $tanks = Tank::with('product')
            ->latest()
            ->get();

        return view('tanks.index', compact('tanks'));
    }



    /*
    |--------------------------------------------------------------------------
    | CREATE FORM
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        $products = Product::where('status', 1)->get();

        return view('tanks.create', compact('products'));
    }



    /*
    |--------------------------------------------------------------------------
    | STORE TANK
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'tank_number' => 'required|unique:tanks,tank_number',
            'capacity_liters' => 'required|numeric|min:1',
            'current_stock_liters' => 'required|numeric|min:0',
            'minimum_level' => 'required|numeric|min:0',
        ]);

        Tank::create([
            'product_id' => $request->product_id,
            'tank_number' => $request->tank_number,
            'capacity_liters' => $request->capacity_liters,
            'current_stock_liters' => $request->current_stock_liters,
            'minimum_level' => $request->minimum_level,
            'status' => $request->status ? 1 : 0,
        ]);

        return redirect()
            ->route('tanks.index')
            ->with('success', 'Tank created successfully.');
    }



    /*
    |--------------------------------------------------------------------------
    | EDIT FORM
    |--------------------------------------------------------------------------
    */

    public function edit(Tank $tank)
    {
        $products = Product::where('status', 1)->get();

        return view('tanks.edit', compact(
            'tank',
            'products'
        ));
    }



    /*
    |--------------------------------------------------------------------------
    | UPDATE TANK
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, Tank $tank)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'tank_number' => 'required|unique:tanks,tank_number,' . $tank->id,
            'capacity_liters' => 'required|numeric|min:1',
            'current_stock_liters' => 'required|numeric|min:0',
            'minimum_level' => 'required|numeric|min:0',
        ]);

        $tank->update([
            'product_id' => $request->product_id,
            'tank_number' => $request->tank_number,
            'capacity_liters' => $request->capacity_liters,
            'current_stock_liters' => $request->current_stock_liters,
            'minimum_level' => $request->minimum_level,
            'status' => $request->status ? 1 : 0,
        ]);

        return redirect()
            ->route('tanks.index')
            ->with('success', 'Tank updated successfully.');
    }



    /*
    |--------------------------------------------------------------------------
    | DELETE TANK
    |--------------------------------------------------------------------------
    */

    public function destroy(Tank $tank)
    {
        $tank->delete();

        return redirect()
            ->route('tanks.index')
            ->with('success', 'Tank deleted successfully.');
    }
}