@extends('layouts.app')

@section('content')
<div class="page-card">
    <h3 class="page-title">Create Employee</h3>
    <p class="page-subtitle">Add a new station employee.</p>

    <form method="POST" action="{{ route('employees.store') }}">
        @csrf
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Employee Code *</label>
                <input type="text" name="employee_code" class="form-control" value="{{ old('employee_code') }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Full Name *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">CNIC</label>
                <input type="text" name="cnic" class="form-control" value="{{ old('cnic') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Salary (PKR)</label>
                <input type="number" name="salary" class="form-control" step="0.01" min="0" value="{{ old('salary', 0) }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Joining Date</label>
                <input type="date" name="joining_date" class="form-control" value="{{ old('joining_date', date('Y-m-d')) }}">
            </div>
            <div class="col-12 mb-3">
                <div class="form-check">
                    <input type="checkbox" name="status" value="1" class="form-check-input" checked id="status">
                    <label class="form-check-label" for="status">Active</label>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-success">Save Employee</button>
        <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
