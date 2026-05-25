@extends('layouts.app')

@section('content')
<div class="page-card">
    <h3 class="page-title">Edit Product</h3>
    <form method="POST" action="{{ route('products.update', $product) }}">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label class="form-label">Name *</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Unit *</label>
            <input type="text" name="unit" class="form-control" value="{{ old('unit', $product->unit) }}" required>
        </div>
        <div class="form-check mb-3">
            <input type="checkbox" name="status" value="1" class="form-check-input" id="status" {{ $product->status ? 'checked' : '' }}>
            <label class="form-check-label" for="status">Active</label>
        </div>
        <button type="submit" class="btn btn-success">Update</button>
        <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
