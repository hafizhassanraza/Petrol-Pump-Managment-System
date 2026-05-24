<?php

namespace App\Http\Controllers;

use App\Models\Dispenser;
use Illuminate\Http\Request;

class DispenserController extends Controller
{
    public function index()
    {
        $dispensers = Dispenser::withCount('nozzles')
            ->latest()
            ->get();

        return view('dispensers.index', compact('dispensers'));
    }

    public function create()
    {
        return view('dispensers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'dispenser_code' => 'required|unique:dispensers,dispenser_code',
            'company' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
        ]);

        Dispenser::create([
            'dispenser_code' => $request->dispenser_code,
            'company' => $request->company,
            'model' => $request->model,
            'status' => $request->status ? 1 : 0,
        ]);

        return redirect()
            ->route('dispensers.index')
            ->with('success', 'Dispenser created successfully.');
    }

    public function edit(Dispenser $dispenser)
    {
        return view('dispensers.edit', compact('dispenser'));
    }

    public function update(Request $request, Dispenser $dispenser)
    {
        $request->validate([
            'dispenser_code' => 'required|unique:dispensers,dispenser_code,' . $dispenser->id,
        ]);

        $dispenser->update([
            'dispenser_code' => $request->dispenser_code,
            'company' => $request->company,
            'model' => $request->model,
            'status' => $request->status ? 1 : 0,
        ]);

        return redirect()
            ->route('dispensers.index')
            ->with('success', 'Dispenser updated successfully.');
    }

    public function destroy(Dispenser $dispenser)
    {
        $dispenser->delete();

        return redirect()
            ->route('dispensers.index')
            ->with('success', 'Dispenser deleted successfully.');
    }
}