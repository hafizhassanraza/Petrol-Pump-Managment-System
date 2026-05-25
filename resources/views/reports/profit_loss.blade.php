@extends('layouts.app')

@section('content')

<h2>Profit & Loss Report</h2>

<div class="card p-3">

    <p>Total Sales: <b>{{ $sales }}</b></p>

    <p>Total Expenses: <b>{{ $expenses }}</b></p>

    <p>Owner Fuel Usage: <b>{{ $ownerFuel }}</b></p>

    <hr>

    <h4>Net Profit: {{ $netProfit }}</h4>

    <div class="d-flex justify-content-end">
        <a href="{{ route('reports.profit-loss.pdf') }}" class="btn btn-danger me-2">⬇ PDF</a>
        <a href="{{ route('reports.profit-loss.csv') }}" class="btn btn-secondary">⬇ CSV</a>
    </div>

</div>

@endsection