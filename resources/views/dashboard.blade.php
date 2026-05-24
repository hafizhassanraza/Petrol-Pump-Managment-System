@extends('layouts.app')

@section('content')

<h2 class="mb-4">Dashboard Analytics</h2>

{{-- KPI CARDS --}}
<div class="row">

    <div class="col-md-3 mb-3">
        <div class="card p-3 text-white bg-primary">
            <h6>Products</h6>
            <h3>{{ $products }}</h3>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card p-3 text-white bg-success">
            <h6>Tanks</h6>
            <h3>{{ $tanks }}</h3>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card p-3 text-white bg-dark">
            <h6>Dispensers</h6>
            <h3>{{ $dispensers }}</h3>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card p-3 text-white bg-danger">
            <h6>Nozzles</h6>
            <h3>{{ $nozzles }}</h3>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card p-3 text-white bg-warning">
            <h6>Employees</h6>
            <h3>{{ $employees }}</h3>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card p-3 text-white bg-info">
            <h6>Active Shifts</h6>
            <h3>{{ $activeShifts }}</h3>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card p-3 bg-light">
            <h6>Today Sales</h6>
            <h3>{{ number_format($todaySales, 2) }}</h3>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card p-3 bg-light">
            <h6>Today Expense</h6>
            <h3>{{ number_format($todayExpense, 2) }}</h3>
        </div>
    </div>

</div>

{{-- CHARTS SECTION --}}
<div class="row mt-4">

    <div class="col-md-8">
        <div class="card p-3">
            <h5>Sales Last 7 Days</h5>

            <canvas id="salesChart"></canvas>

        </div>
    </div>

    <div class="col-md-4">
        <div class="card p-3">
            <h5>Quick Summary</h5>

            <p><b>Profit Estimate:</b>
                {{ number_format($todaySales - $todayExpense, 2) }}
            </p>

            <p><b>Avg Daily Sales:</b>
                {{ number_format(collect($salesData)->avg(), 2) }}
            </p>

        </div>
    </div>

</div>

{{-- CHART JS --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const ctx = document.getElementById('salesChart');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: @json($salesLabels),
        datasets: [{
            label: 'Sales',
            data: @json($salesData),
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true
            }
        }
    }
});
</script>

@endsection