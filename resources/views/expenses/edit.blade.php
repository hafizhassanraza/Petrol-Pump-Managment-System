@extends('layouts.app')

@section('content')

<div class="page-card">
    <h3 class="page-title">Edit Expense</h3>
    <p class="page-subtitle">{{ $expense->expense_type }} — {{ \Carbon\Carbon::parse($expense->expense_date)->format('d M Y') }}</p>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('expenses.update', $expense) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Expense Type</label>
            <select name="expense_type" class="form-control" required>
                @foreach($expenseTypes as $type)
                    <option value="{{ $type }}" @selected(old('expense_type', $expense->expense_type) === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Amount (PKR)</label>
            <input type="number" step="0.01" name="amount" class="form-control"
                   value="{{ old('amount', $expense->amount) }}" required>
        </div>

        <div class="mb-3">
            <label>Date</label>
            <input type="date" name="expense_date" class="form-control"
                   value="{{ old('expense_date', \Carbon\Carbon::parse($expense->expense_date)->format('Y-m-d')) }}" required>
        </div>

        <div class="mb-3">
            <label>Notes</label>
            <textarea name="notes" class="form-control">{{ old('notes', $expense->notes) }}</textarea>
        </div>

        <button class="btn btn-primary">Save Changes</button>
        <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Back</a>
    </form>
</div>

@endsection
