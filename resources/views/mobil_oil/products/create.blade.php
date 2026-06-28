@extends('layouts.app')

@section('content')
<div class="page-card">
    <h3 class="page-title">Add Mobil Oil Product</h3>
    <p class="page-subtitle">Add a lubricant / Mobil Oil item for purchase and sale tracking.</p>

    <form method="POST" action="{{ route('mobil-oil.products.store') }}">
        @csrf

        <div class="mb-3">
            <label>Product Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
        </div>

        <div class="mb-3">
            <label>SKU / Code</label>
            <input type="text" name="sku" class="form-control" value="{{ old('sku') }}">
        </div>

        <div class="mb-3">
            <label>Unit</label>
            <select name="unit" class="form-control">
                <option value="bottle" @selected(old('unit', 'bottle') === 'bottle')>Bottle</option>
                <option value="carton" @selected(old('unit') === 'carton')>Carton</option>
                <option value="liter" @selected(old('unit') === 'liter')>Liter</option>
                <option value="piece" @selected(old('unit') === 'piece')>Piece</option>
            </select>
        </div>

        <div class="mb-3">
            <label>Minimum Stock Level</label>
            <input type="number" step="0.01" name="minimum_level" class="form-control" value="{{ old('minimum_level', 0) }}">
        </div>

        <div class="mb-3">
            <label>Selling Price (PKR)</label>
            <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price') }}">
            <small class="text-muted">Optional — can be set later from Edit.</small>
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" name="status" class="form-check-input" checked>
            <label>Active</label>
        </div>

        <button class="btn btn-success">Save</button>
        <a href="{{ route('mobil-oil.products.index') }}" class="btn btn-secondary">Back</a>
    </form>
</div>
@endsection
