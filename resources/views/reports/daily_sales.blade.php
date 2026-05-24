@extends('layouts.app')

@section('content')

<h2>Daily Sales Report</h2>

<form method="GET">

    <input type="date" name="from">
    <input type="date" name="to">

    <button class="btn btn-primary btn-sm">
        Filter
    </button>

</form>

<div class="d-flex justify-content-end mb-3">

    <a href="{{ route('reports.daily-sales.pdf') }}"
       class="btn btn-danger">

        ⬇ Download PDF
    </a>

</div>

<br>

<table class="table table-bordered">

    <thead>
        <tr>
            <th>Employee</th>
            <th>Nozzle</th>
            <th>Liters</th>
            <th>Amount</th>
            <th>Date</th>
        </tr>
    </thead>

    <tbody>

        @foreach($shifts as $s)

        <tr>
            <td>{{ $s->employee->name ?? '' }}</td>
            <td>{{ $s->nozzle->nozzle_number ?? '' }}</td>
            <td>{{ $s->total_liters }}</td>
            <td>{{ $s->total_amount }}</td>
            <td>{{ $s->created_at }}</td>
        </tr>

        @endforeach

    </tbody>

</table>

@endsection