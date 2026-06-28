<?php

namespace App\Http\Controllers;

use App\Models\MobilOilProduct;
use App\Services\MobilOilPriceService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MobilOilProductController extends Controller
{
    public function index()
    {
        $products = MobilOilProduct::with('latestPrice')
            ->orderBy('name')
            ->paginate(15);

        return view('mobil_oil.products.index', compact('products'));
    }

    public function create()
    {
        return view('mobil_oil.products.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100|unique:mobil_oil_products,sku',
            'unit' => 'required|string|max:50',
            'minimum_level' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0.01',
        ]);

        $product = MobilOilProduct::create([
            'name' => $request->name,
            'sku' => $request->sku,
            'unit' => $request->unit,
            'minimum_level' => $request->minimum_level ?? 0,
            'status' => $request->status ? 1 : 0,
        ]);

        if ($request->filled('price')) {
            MobilOilPriceService::setPrice($product->id, (float) $request->price);
        }

        return redirect()
            ->route('mobil-oil.products.index')
            ->with('success', 'Mobil Oil product added successfully.');
    }

    public function edit(MobilOilProduct $product)
    {
        $product->load('latestPrice');
        $priceHistory = $product->prices()->latest('effective_from')->limit(10)->get();

        return view('mobil_oil.products.edit', compact('product', 'priceHistory'));
    }

    public function update(Request $request, MobilOilProduct $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100|unique:mobil_oil_products,sku,' . $product->id,
            'unit' => 'required|string|max:50',
            'minimum_level' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0.01',
            'effective_from' => 'nullable|date|required_with:price',
        ]);

        $product->update([
            'name' => $request->name,
            'sku' => $request->sku,
            'unit' => $request->unit,
            'minimum_level' => $request->minimum_level ?? 0,
            'status' => $request->status ? 1 : 0,
        ]);

        if ($request->filled('price')) {
            MobilOilPriceService::setPrice(
                $product->id,
                (float) $request->price,
                $request->filled('effective_from') ? Carbon::parse($request->effective_from) : now()
            );
        }

        return redirect()
            ->route('mobil-oil.products.index')
            ->with('success', 'Mobil Oil product updated successfully.');
    }
}
