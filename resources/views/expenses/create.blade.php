@extends('layouts.app')

@section('content')

<div class="page-card">
    <h3 class="page-title">Add Expense</h3>
    <p class="page-subtitle">Record a new operating expense. Employee salaries are managed separately.</p>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('expenses.store') }}">
        @csrf

        <div class="mb-3">
            <label>Expense Type *</label>
            <select name="expense_type" class="form-control" required>
                <option value="">Select type</option>
                @foreach($expenseTypes as $type)
                    <option value="{{ $type }}" @selected(old('expense_type') === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Amount (PKR) *</label>
            <input type="number" step="0.01" min="0.01" name="amount" class="form-control"
                   value="{{ old('amount') }}" required>
        </div>

        <div class="mb-3">
            <label>Date *</label>
            <input type="date" name="expense_date" class="form-control"
                   value="{{ old('expense_date', $defaultDate) }}" required>
        </div>

        <div class="mb-3">
            <label>Notes</label>
            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
        </div>

        <button class="btn btn-success">Save Expense</button>
        <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Back</a>
    </form>
</div>

@endsection
