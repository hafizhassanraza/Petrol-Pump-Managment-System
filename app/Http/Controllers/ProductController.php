<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | DISPLAY PRODUCTS
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $products = Product::latest()->get();

        return view('products.index', compact('products'));
    }



    /*
    |--------------------------------------------------------------------------
    | CREATE FORM
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        return view('products.create');
    }



    /*
    |--------------------------------------------------------------------------
    | STORE PRODUCT
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
        ]);

        Product::create([
            'name' => $request->name,
            'unit' => $request->unit,
            'status' => $request->status ? 1 : 0,
        ]);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product created successfully.');
    }



    /*
    |--------------------------------------------------------------------------
    | EDIT FORM
    |--------------------------------------------------------------------------
    */

    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }



    /*
    |--------------------------------------------------------------------------
    | UPDATE PRODUCT
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
        ]);

        $product->update([
            'name' => $request->name,
            'unit' => $request->unit,
            'status' => $request->status ? 1 : 0,
        ]);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product updated successfully.');
    }



    /*
    |--------------------------------------------------------------------------
    | DELETE PRODUCT
    |--------------------------------------------------------------------------
    */

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }
}