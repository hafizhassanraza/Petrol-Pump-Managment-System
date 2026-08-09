@extends('layouts.app')

@section('content')
<div class="page-card">
    <h3 class="page-title">Edit Agency Customer</h3>

    <form method="POST" action="{{ route('agency-customers.update', $customer) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $customer->name) }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $customer->phone) }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">CNIC</label>
                <input type="text" name="cnic" class="form-control" value="{{ old('cnic', $customer->cnic) }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control" value="{{ old('address', $customer->address) }}">
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $customer->notes) }}</textarea>
            </div>
            <div class="col-12 mb-3">
                <div class="form-check">
                    <input type="checkbox" name="status" value="1" class="form-check-input" id="status" @checked(old('status', $customer->status))>
                    <label class="form-check-label" for="status">Active</label>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="{{ route('agency-customers.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
