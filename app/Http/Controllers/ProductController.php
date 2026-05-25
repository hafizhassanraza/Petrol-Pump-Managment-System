<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('latestPrice')->latest()->paginate(15);

        return view('products.index', compact('products'));
    }

    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
        ]);

        $product->update([
            'name' => $request->name,
            'unit' => $request->unit,
            'status' => $request->boolean('status'),
        ]);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product updated successfully.');
    }
}
