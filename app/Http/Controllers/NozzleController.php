<?php

namespace App\Http\Controllers;

use App\Models\Nozzle;
use App\Models\Dispenser;
use App\Models\Tank;
use App\Models\Product;
use Illuminate\Http\Request;

class NozzleController extends Controller
{
    public function index()
    {
        $nozzles = Nozzle::with(['dispenser', 'tank', 'product'])
            ->latest()
            ->get();

        return view('nozzles.index', compact('nozzles'));
    }

    public function create()
    {
        return view('nozzles.create', [
            'dispensers' => Dispenser::where('status', 1)->get(),
            'tanks' => Tank::where('status', 1)->get(),
            'products' => Product::where('status', 1)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'dispenser_id' => 'required|exists:dispensers,id',
            'tank_id' => 'required|exists:tanks,id',
            'product_id' => 'required|exists:products,id',
            'nozzle_number' => 'required|unique:nozzles,nozzle_number',
            'current_meter_reading' => 'required|numeric|min:0',
        ]);

        // IMPORTANT BUSINESS RULE:
        // Tank product must match nozzle product

        $tank = Tank::find($request->tank_id);

        if ($tank->product_id != $request->product_id) {
            return back()->with('error', 'Tank product and nozzle product mismatch!');
        }

        Nozzle::create([
            'dispenser_id' => $request->dispenser_id,
            'tank_id' => $request->tank_id,
            'product_id' => $request->product_id,
            'nozzle_number' => $request->nozzle_number,
            'current_meter_reading' => $request->current_meter_reading,
            'status' => $request->status ? 1 : 0,
        ]);

        return redirect()
            ->route('nozzles.index')
            ->with('success', 'Nozzle created successfully.');
    }

    public function edit(Nozzle $nozzle)
    {
        return view('nozzles.edit', [
            'nozzle' => $nozzle,
            'dispensers' => Dispenser::where('status', 1)->get(),
            'tanks' => Tank::where('status', 1)->get(),
            'products' => Product::where('status', 1)->get(),
        ]);
    }

    public function update(Request $request, Nozzle $nozzle)
    {
        $request->validate([
            'dispenser_id' => 'required|exists:dispensers,id',
            'tank_id' => 'required|exists:tanks,id',
            'product_id' => 'required|exists:products,id',
            'nozzle_number' => 'required|unique:nozzles,nozzle_number,' . $nozzle->id,
            'current_meter_reading' => 'required|numeric|min:0',
        ]);

        $tank = Tank::find($request->tank_id);

        if ($tank->product_id != $request->product_id) {
            return back()->with('error', 'Tank product and nozzle product mismatch!');
        }

        $nozzle->update([
            'dispenser_id' => $request->dispenser_id,
            'tank_id' => $request->tank_id,
            'product_id' => $request->product_id,
            'nozzle_number' => $request->nozzle_number,
            'current_meter_reading' => $request->current_meter_reading,
            'status' => $request->status ? 1 : 0,
        ]);

        return redirect()
            ->route('nozzles.index')
            ->with('success', 'Nozzle updated successfully.');
    }

    public function destroy(Nozzle $nozzle)
    {
        $nozzle->delete();

        return redirect()
            ->route('nozzles.index')
            ->with('success', 'Nozzle deleted successfully.');
    }
}