@extends('layouts.app')

@section('content')
@include('partials.period-filter')

<div class="page-card">
    <div class="list-toolbar">
        <a href="{{ route('expenses.create') }}" class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Add Expense</a>
    </div>
    <div class="table-container">
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Amount (PKR)</th>
                    <th>Date</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $e)
                    <tr>
                        <td><strong>{{ $e->expense_type }}</strong></td>
                        <td>{{ number_format($e->amount, 2) }}</td>
                        <td>{{ \Carbon\Carbon::parse($e->expense_date)->format('d M Y') }}</td>
                        <td>{{ $e->notes ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No expenses in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $expenses->links() }}
</div>
@endsection
