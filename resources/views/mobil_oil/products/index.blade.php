@extends('layouts.app')

@section('content')
<div class="page-card">
    <div class="list-toolbar">
        <a href="{{ route('mobil-oil.products.create') }}" class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Add Product</a>
    </div>
    <div class="table-container">
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>SKU</th>
                    <th>Unit</th>
                    <th>Stock</th>
                    <th>Min Level</th>
                    <th>Selling Price</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $p)
                    <tr class="{{ (float) $p->current_stock_qty <= (float) $p->minimum_level ? 'row-low' : '' }}">
                        <td><strong>{{ $p->name }}</strong></td>
                        <td>{{ $p->sku ?? '—' }}</td>
                        <td>{{ $p->unit }}</td>
                        <td>{{ number_format($p->current_stock_qty, 2) }}</td>
                        <td>{{ number_format($p->minimum_level, 2) }}</td>
                        <td>
                            @if($p->latestPrice)
                                PKR {{ number_format($p->latestPrice->price, 2) }}
                            @else
                                <span class="text-danger">Not set</span>
                            @endif
                        </td>
                        <td>{{ $p->status ? 'Active' : 'Inactive' }}</td>
                        <td><a href="{{ route('mobil-oil.products.edit', $p) }}" class="btn btn-sm btn-primary">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No Mobil Oil products yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $products->links() }}
</div>
@endsection
