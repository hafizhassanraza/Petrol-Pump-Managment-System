@extends('layouts.app')

@section('content')
<div class="page-card">
    <div class="list-toolbar">
        <a href="{{ route('product-prices.index') }}" class="btn btn-outline-success btn-sm"><i class="bi bi-tag"></i> Manage Prices</a>
    </div>
    <div class="table-container">
        <table class="excel-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Unit</th>
                    <th>Current Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>{{ $product->id }}</td>
                        <td><strong>{{ $product->name }}</strong></td>
                        <td>{{ $product->unit }}</td>
                        <td>
                            @if($product->latestPrice)
                                <strong>PKR {{ number_format($product->latestPrice->price, 2) }}</strong>
                            @else
                                <span class="text-danger">Not set</span>
                            @endif
                        </td>
                        <td>
                            @if($product->status)<span class="status-active">Active</span>
                            @else<span class="status-inactive">Inactive</span>@endif
                        </td>
                        <td>
                            <a href="{{ route('products.edit', $product) }}" class="btn btn-warning btn-sm">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No products. Add manually in database if needed.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $products->links() }}
</div>
@endsection
