@extends('layouts.app')

@section('content')

<div class="page-card">
    <h3 class="page-title">Edit Employee Salary</h3>
    <p class="page-subtitle">{{ $salary->employee->name ?? 'Employee' }} — {{ $salary->typeLabel() }}</p>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('employee-salaries.update', $salary) }}">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-md-6 mb-3">
                <label>Employee *</label>
                <select name="employee_id" class="form-control" required>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}"
                                @selected((string) old('employee_id', $salary->employee_id) === (string) $employee->id)>
                            {{ $employee->name }} ({{ $employee->employee_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label>Payment Type *</label>
                <select name="type" class="form-control" required>
                    @foreach($typeLabels as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $salary->type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label>Amount (PKR) *</label>
                <input type="number" step="0.01" min="0.01" name="amount" class="form-control"
                       value="{{ old('amount', $salary->amount) }}" required>
            </div>
            <div class="col-md-4 mb-3">
                <label>Payment Date *</label>
                <input type="date" name="payment_date" class="form-control"
                       value="{{ old('payment_date', $salary->payment_date->format('Y-m-d')) }}" required>
            </div>
            <div class="col-md-4 mb-3">
                <label>Salary Month *</label>
                <input type="month" name="salary_month" class="form-control"
                       value="{{ old('salary_month', $salary->salary_month->format('Y-m')) }}" required>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label>Payment Method *</label>
                <select name="payment_method" class="form-control" required>
                    @foreach($paymentMethods as $method)
                        <option value="{{ $method }}" @selected(old('payment_method', $salary->payment_method) === $method)>
                            {{ ucfirst($method) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label>Reference No</label>
                <input type="text" name="reference_no" class="form-control"
                       value="{{ old('reference_no', $salary->reference_no) }}">
            </div>
            <div class="col-md-4 mb-3">
                <label>Notes</label>
                <input type="text" name="notes" class="form-control" value="{{ old('notes', $salary->notes) }}">
            </div>
        </div>

        <button class="btn btn-success">Update</button>
        <a href="{{ route('employee-salaries.index') }}" class="btn btn-secondary">Back</a>
    </form>
</div>

@endsection
