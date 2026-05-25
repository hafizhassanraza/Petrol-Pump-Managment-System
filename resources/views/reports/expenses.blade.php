@extends('layouts.app')

@section('content')

<h2>Expense Report</h2>
<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('reports.expenses.pdf') }}" class="btn btn-danger me-2">⬇ PDF</a>
    <a href="{{ route('reports.expenses.csv') }}" class="btn btn-secondary">⬇ CSV</a>
</div>

<table class="table table-bordered">

    <thead>
        <tr>
            <th>Type</th>
            <th>Amount</th>
            <th>Date</th>
        </tr>
    </thead>

    <tbody>

        @foreach($expenses as $e)

        <tr>
            <td>{{ $e->expense_type }}</td>
            <td>{{ $e->amount }}</td>
            <td>{{ $e->expense_date }}</td>
        </tr>

        @endforeach

    </tbody>

</table>

@endsection