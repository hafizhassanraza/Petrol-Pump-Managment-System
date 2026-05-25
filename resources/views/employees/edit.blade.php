@extends('layouts.app')

@section('content')
<div class="page-card">
    <h3 class="page-title">Edit Employee</h3>
    <p class="page-subtitle">{{ $employee->name }} ({{ $employee->employee_code }})</p>

    <form method="POST" action="{{ route('employees.update', $employee) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Employee Code *</label>
                <input type="text" name="employee_code" class="form-control" value="{{ old('employee_code', $employee->employee_code) }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Full Name *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $employee->name) }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">CNIC</label>
                <input type="text" name="cnic" class="form-control" value="{{ old('cnic', $employee->cnic) }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $employee->phone) }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Salary (PKR)</label>
                <input type="number" name="salary" class="form-control" step="0.01" min="0" value="{{ old('salary', $employee->salary) }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Joining Date</label>
                <input type="date" name="joining_date" class="form-control" value="{{ old('joining_date', $employee->joining_date) }}">
            </div>
            <div class="col-12 mb-3">
                <div class="form-check">
                    <input type="checkbox" name="status" value="1" class="form-check-input" id="status" {{ $employee->status ? 'checked' : '' }}>
                    <label class="form-check-label" for="status">Active</label>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-success">Update</button>
        <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
