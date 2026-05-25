<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Services\ProductPriceService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProductPriceController extends Controller
{
    public function index()
    {
        $prices = ProductPrice::with(['product', 'creator'])
            ->latest('effective_from')
            ->paginate(20);

        $products = Product::where('status', 1)
            ->with('latestPrice')
            ->orderBy('name')
            ->get();

        return view('product_prices.index', compact('prices', 'products'));
    }

    public function create()
    {
        return view('product_prices.create', [
            'products' => Product::where('status', 1)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
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
