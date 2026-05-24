@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between mb-3">

    <h2>Expenses</h2>

    <a href="{{ route('expenses.create') }}"
       class="btn btn-primary">

        Add Expense

    </a>

</div>


@if(session('success'))

    <div class="alert alert-success">
        {{ session('success') }}
    </div>

@endif


<table class="table table-bordered">

    <thead>

        <tr>

            <th>ID</th>

            <th>Type</th>

            <th>Amount</th>

            <th>Date</th>

            <th>Notes</th>

        </tr>

    </thead>

    <tbody>

        @foreach($expenses as $e)

            <tr>

                <td>{{ $e->id }}</td>

                <td>{{ $e->expense_type }}</td>

                <td>{{ $e->amount }}</td>

                <td>{{ $e->expense_date }}</td>

                <td>{{ $e->notes }}</td>

            </tr>

        @endforeach

    </tbody>

</table>

@endsection