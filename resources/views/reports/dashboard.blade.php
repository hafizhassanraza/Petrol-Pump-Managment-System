@extends('layouts.app')

@section('content')

<h2>Reports Dashboard</h2>

<div class="row g-3">

    <div class="col-md-3">
        <a href="{{ route('reports.daily-sales') }}" class="btn btn-primary w-100">
            Daily Sales
        </a>
    </div>

    <div class="col-md-3">
        <a href="{{ route('reports.profit-loss') }}" class="btn btn-success w-100">
            Profit & Loss
        </a>
    </div>

    <div class="col-md-3">
        <a href="{{ route('reports.stock') }}" class="btn btn-warning w-100">
            Stock Report
        </a>
    </div>

    <div class="col-md-3">
        <a href="{{ route('reports.expenses') }}" class="btn btn-danger w-100">
            Expense Report
        </a>
    </div>

</div>

<br>

<div class="row g-3">

    <div class="col-md-3">
        <a href="{{ route('reports.variance') }}" class="btn btn-dark w-100">
            Variance Report
        </a>
    </div>

</div>

@endsection