@extends('layouts.app')

@section('content')
<div class="page-card">
    <h3 class="page-title">Edit Mobil Oil Product</h3>

    <form method="POST" action="{{ route('mobil-oil.products.update', $product) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Product Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
        </div>

        <div class="mb-3">
            <label>SKU / Code</label>
            <input type="text" name="sku" class="form-control" value="{{ old('sku', $product->sku) }}">
        </div>

        <div class="mb-3">
            <label>Unit</label>
            <select name="unit" class="form-control">
                @foreach(['bottle', 'carton', 'liter', 'piece'] as $unit)
                    <option value="{{ $unit }}" @selected(old('unit', $product->unit) === $unit)>{{ ucfirst($unit) }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Current Stock</label>
            <input type="text" class="form-control" value="{{ number_format($product->current_stock_qty, 2) }} {{ $product->unit }}" disabled>
            <small class="text-muted">Stock changes via purchases and sales.</small>
        </div>

        <div class="mb-3">
            <label>Minimum Stock Level</label>
            <input type="number" step="0.01" name="minimum_level" class="form-control" value="{{ old('minimum_level', $product->minimum_level) }}">
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" name="status" class="form-check-input" {{ $product->status ? 'checked' : '' }}>
            <label>Active</label>
        </div>

        <hr class="my-4">

        <h5 class="mb-3">Selling Price</h5>

        @if($product->latestPrice)
            <div class="alert alert-light border mb-3">
                <strong>Current price:</strong> PKR {{ number_format($product->latestPrice->price, 2) }}
                <small class="text-muted d-block">Effective since {{ $product->latestPrice->effective_from->format('d M Y, h:i A') }}</small>
            </div>
        @else
            <div class="alert alert-warning mb-3">No selling price set yet. Enter a price below.</div>
        @endif

        <div class="row">
            <div class="col-md-6 mb-3">
                <label>New Selling Price (PKR)</label>
                <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price') }}" placeholder="Leave blank to keep current price">
            </div>
            <div class="col-md-6 mb-3">
                <label>Effective From</label>
                <input type="datetime-local" name="effective_from" class="form-control" value="{{ old('effective_from', now()->format('Y-m-d\TH:i')) }}">
                <small class="text-muted">Only used when you enter a new price.</small>
            </div>
        </div>

        <button class="btn btn-primary">Update</button>
        <a href="{{ route('mobil-oil.products.index') }}" class="btn btn-secondary">Back</a>
    </form>
</div>

@if($priceHistory->count() > 0)
<div class="page-card mt-3">
    <h5 class="mb-3">Price History</h5>
    <div class="table-container">
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Price (PKR)</th>
                    <th>Effective From</th>
                    <th>Recorded</th>
                </tr>
            </thead>
            <tbody>
                @foreach($priceHistory as $row)
                    <tr>
                        <td>{{ number_format($row->price, 2) }}</td>
                        <td>{{ $row->effective_from->format('d M Y, h:i A') }}</td>
                        <td>{{ $row->created_at->format('d M Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
