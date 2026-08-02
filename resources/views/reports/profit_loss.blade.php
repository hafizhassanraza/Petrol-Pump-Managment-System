@extends('layouts.app')

@section('content')

@include('reports.partials.report-styles')

<div class="row mb-4">
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card amount">
            <div class="info-card-icon"><i class="bi bi-cash-coin"></i></div>
            <div class="info-card-label">Total Sales</div>
            <div class="info-card-value">PKR {{ money($sales) }}</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card secondary">
            <div class="info-card-icon"><i class="bi bi-wallet2"></i></div>
            <div class="info-card-label">Total Costs</div>
            <div class="info-card-value">PKR {{ money($totalCosts) }}</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card {{ $netProfit >= 0 ? 'success' : 'danger' }}">
            <div class="info-card-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="info-card-label">Net Profit</div>
            <div class="info-card-value">PKR {{ money($netProfit) }}</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card warning">
            <div class="info-card-icon"><i class="bi bi-percent"></i></div>
            <div class="info-card-label">Profit Margin</div>
            <div class="info-card-value">{{ $profitMargin }}%</div>
        </div>
    </div>
</div>

<div class="filter-section">
    <h5><i class="bi bi-funnel"></i> Filter Period</h5>
    <form method="GET" id="filterForm">
        <div class="filter-options">
            <button type="button" class="filter-btn @if($filter === 'today') active @endif" onclick="setFilter('today')">
                <i class="bi bi-calendar-check"></i> Today
            </button>
            <button type="button" class="filter-btn @if($filter === 'last-week') active @endif" onclick="setFilter('last-week')">
                <i class="bi bi-calendar-week"></i> Last 7 Days
            </button>
            <button type="button" class="filter-btn @if($filter === 'last-month') active @endif" onclick="setFilter('last-month')">
                <i class="bi bi-calendar-month"></i> Last 30 Days
            </button>
            <button type="button" class="filter-btn @if($filter === 'custom') active @endif" onclick="setFilter('custom')">
                <i class="bi bi-calendar-range"></i> Custom Range
            </button>
        </div>
        <div class="date-range-group" id="customDateRange" style="display: @if($filter === 'custom') flex @else none @endif;">
            <div class="date-input-group">
                <label for="from">From Date</label>
                <input type="date" id="from" name="from" value="{{ $from }}" class="form-control">
            </div>
            <div class="date-input-group">
                <label for="to">To Date</label>
                <input type="date" id="to" name="to" value="{{ $to }}" class="form-control">
            </div>
            <button type="submit" class="btn-filter"><i class="bi bi-search"></i> Apply Filter</button>
        </div>
        <input type="hidden" id="filterInput" name="filter" value="{{ $filter }}">
    </form>
    <div class="download-section mt-3">
        <a href="{{ route('reports.profit-loss.pdf', ['from' => $from, 'to' => $to, 'filter' => $filter]) }}" class="btn-download btn-download-pdf">
            <i class="bi bi-file-pdf"></i> Download PDF
        </a>
        <a href="{{ route('reports.profit-loss.csv', ['from' => $from, 'to' => $to, 'filter' => $filter]) }}" class="btn-download btn-download-excel">
            <i class="bi bi-file-earmark-spreadsheet"></i> Download Excel
        </a>
    </div>
</div>

{{-- Page 1: Sales & Profit --}}
@include('reports.partials.product_breakdown', ['fuelBreakdownSimple' => true])
@include('reports.partials.mobil_oil_breakdown')

<div class="table-container mt-4">
    <h5 class="section-heading p-3 mb-0" style="font-size:16px;font-weight:600;color:#1e293b;">
        <i class="bi bi-bar-chart"></i> Total Sales &amp; Profit
    </h5>
    <table class="excel-table">
        <thead>
            <tr>
                <th>Category</th>
                <th style="text-align: right;">Sales (PKR)</th>
                <th style="text-align: right;">Profit/Loss (PKR)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Petroleum</strong></td>
                <td style="text-align: right;">{{ money($fuelSales) }}</td>
                <td style="text-align: right; font-weight: 600;" class="{{ $fuelSalesProfit >= 0 ? 'text-profit' : 'text-loss' }}">{{ money($fuelSalesProfit) }}</td>
            </tr>
            <tr>
                <td><strong>Mobil Oil</strong></td>
                <td style="text-align: right;">{{ money($mobilOilSales) }}</td>
                <td style="text-align: right; font-weight: 600;" class="{{ $mobilOilSalesProfit >= 0 ? 'text-profit' : 'text-loss' }}">{{ money($mobilOilSalesProfit) }}</td>
            </tr>
            <tr style="background:#f8fafc; font-weight:600;">
                <td>Total</td>
                <td style="text-align: right;">{{ money($sales) }}</td>
                <td style="text-align: right;" class="{{ $totalSalesProfit >= 0 ? 'text-profit' : 'text-loss' }}">{{ money($totalSalesProfit) }}</td>
            </tr>
            <tr>
                <td>Operating Expenses</td>
                <td style="text-align: right;">—</td>
                <td style="text-align: right;" class="text-loss">- {{ money($expenses) }}</td>
            </tr>
            <tr>
                <td>Owner Fuel Usage</td>
                <td style="text-align: right;">—</td>
                <td style="text-align: right;" class="text-loss">- {{ money($ownerFuel) }}</td>
            </tr>
            <tr style="background:#f8fafc; font-weight:600;">
                <td>Total Expense</td>
                <td style="text-align: right;">—</td>
                <td style="text-align: right;" class="text-loss">- {{ money($operatingAndOwnerTotal) }}</td>
            </tr>
            <tr style="background:#f0f4f8; font-weight:700;">
                <td>Net Profit (Inc. Total Expense)</td>
                <td style="text-align: right;">—</td>
                <td style="text-align: right;" class="{{ $netSalesProfit >= 0 ? 'text-profit' : 'text-loss' }}">{{ money($netSalesProfit) }}</td>
            </tr>
        </tbody>
    </table>
</div>

<script>
    function setFilter(filterType) {
        document.getElementById('filterInput').value = filterType;
        if (filterType === 'custom') {
            document.getElementById('customDateRange').style.display = 'flex';
        } else {
            document.getElementById('customDateRange').style.display = 'none';
            document.getElementById('filterForm').submit();
        }
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
    }
</script>

@endsection
