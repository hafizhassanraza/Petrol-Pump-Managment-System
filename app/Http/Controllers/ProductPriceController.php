<?php

namespace App\Http\Controllers;

use App\Models\ProductPrice;
use App\Services\ProductPriceService;
use App\Support\FuelProducts;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductPriceController extends Controller
{
    public function index()
    {
        $products = FuelProducts::all()->load('latestPrice');

        $prices = ProductPrice::with(['product', 'creator'])
            ->whereIn('product_id', $products->pluck('id'))
            ->latest('effective_from')
            ->paginate(20);

        return view('product_prices.index', compact('prices', 'products'));
    }

    public function create()
    {
        return view('product_prices.create', [
            'products' => FuelProducts::all()->load('latestPrice'),
        ]);
    }

    public function store(Request $request)
    {
        $fuelIds = FuelProducts::all()->pluck('id')->all();

        $request->validate([
            'product_id' => ['required', Rule::in($fuelIds)],
            'price' => 'required|numeric|min:0.01',
            'effective_from' => 'required|date',
        ]);

        ProductPriceService::setPrice(
            (int) $request->product_id,
            (float) $request->price,
            Carbon::parse($request->effective_from)
        );

        return redirect()
            ->route('product-prices.index')
            ->with('success', 'Selling price updated successfully.');
    }
}
