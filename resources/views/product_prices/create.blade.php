@extends('layouts.app')

@section('content')
<div class="page-card">
    <h3 class="page-title">Set New Selling Price</h3>
    <p class="page-subtitle">Price applies to new shift closes from the effective date/time onward.</p>

    <form method="POST" action="{{ route('product-prices.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Product *</label>
            <select name="product_id" class="form-control" required>
                <option value="">Select product</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}" @selected(old('product_id') == $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">New Price (PKR per liter) *</label>
            <input type="number" name="price" class="form-control" step="0.01" min="0.01" value="{{ old('price') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Effective From *</label>
            <input type="datetime-local" name="effective_from" class="form-control"
                   value="{{ old('effective_from', now()->format('Y-m-d\TH:i')) }}" required>
        </div>
        <button type="submit" class="btn btn-success">Save Price</button>
        <a href="{{ route('product-prices.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
