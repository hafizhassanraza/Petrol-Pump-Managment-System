@extends('layouts.app')

@section('content')

@include('reports.partials.report-styles')

<div class="row mb-4">
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card amount">
            <div class="info-card-icon"><i class="bi bi-cart-check"></i></div>
            <div class="info-card-label">Total Sales</div>
            <div class="info-card-value">PKR {{ money($totalAmount) }}</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card secondary">
            <div class="info-card-icon"><i class="bi bi-box-seam"></i></div>
            <div class="info-card-label">Units Sold</div>
            <div class="info-card-value">{{ number_format($totalQty, 2) }}</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card success">
            <div class="info-card-icon"><i class="bi bi-receipt"></i></div>
            <div class="info-card-label">Transactions</div>
            <div class="info-card-value">{{ $sales->count() }}</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card warning">
            <div class="info-card-icon"><i class="bi bi-tags"></i></div>
            <div class="info-card-label">Products Sold</div>
            <div class="info-card-value">{{ $byProduct->count() }}</div>
        </div>
    </div>
</div>

<div class="filter-section">
    <h5><i class="bi bi-funnel"></i> Filter Period</h5>
    <form method="GET" id="filterForm">
        <div class="filter-options">
            <button type="button" class="filter-btn @if($filter === 'today') active @endif" onclick="setFilter('today')">Today</button>
            <button type="button" class="filter-btn @if($filter === 'last-week') active @endif" onclick="setFilter('last-week')">Last 7 Days</button>
            <button type="button" class="filter-btn @if($filter === 'last-month') active @endif" onclick="setFilter('last-month')">Last 30 Days</button>
            <button type="button" class="filter-btn @if($filter === 'custom') active @endif" onclick="setFilter('custom')">Custom Range</button>
        </div>
        <div class="date-range-group" id="customDateRange" style="display: @if($filter === 'custom') flex @else none @endif;">
            <div class="date-input-group">
                <label for="from">From</label>
                <input type="date" id="from" name="from" value="{{ $from }}" class="form-control">
            </div>
            <div class="date-input-group">
                <label for="to">To</label>
                <input type="date" id="to" name="to" value="{{ $to }}" class="form-control">
            </div>
            <button type="submit" class="btn-filter">Apply</button>
        </div>
        <input type="hidden" id="filterInput" name="filter" value="{{ $filter }}">
    </form>
    <div class="download-section mt-3">
        <a href="{{ route('reports.mobil-oil-sales.pdf', ['from' => $from, 'to' => $to, 'filter' => $filter]) }}" class="btn-download btn-download-pdf">Download PDF</a>
        <a href="{{ route('reports.mobil-oil-sales.csv', ['from' => $from, 'to' => $to, 'filter' => $filter]) }}" class="btn-download btn-download-excel">Download Excel</a>
    </div>
</div>

@if($byProduct->count() > 0)
<div class="table-container mb-4">
    <h5 class="section-heading p-3 mb-0">Sales by Product</h5>
    <table class="excel-table">
        <thead>
            <tr>
                <th>Product</th>
                <th style="text-align: right;">Quantity</th>
                <th style="text-align: right;">Amount (PKR)</th>
                <th style="text-align: right;">Sales</th>
            </tr>
        </thead>
        <tbody>
            @foreach($byProduct as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td style="text-align: right;">{{ number_format($row['total_qty'], 2) }} {{ $row['unit'] }}</td>
                    <td style="text-align: right;">{{ money($row['total_amount']) }}</td>
                    <td style="text-align: right;">{{ $row['record_count'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="table-container">
    <h5 class="section-heading p-3 mb-0">All Sales</h5>
    <table class="excel-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Employee</th>
                <th>Sold At</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $s)
                <tr>
                    <td>{{ $s->product->name ?? '—' }}</td>
                    <td>{{ number_format($s->quantity, 2) }} {{ $s->product->unit ?? '' }}</td>
                    <td>{{ rate($s->unit_price) }}</td>
                    <td><strong>{{ money($s->total_amount) }}</strong></td>
                    <td>{{ ucfirst($s->payment_method) }}</td>
                    <td>{{ $s->employee->name ?? '—' }}</td>
                    <td>{{ $s->sold_datetime?->format('d M Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No Mobil Oil sales in this period.</td></tr>
            @endforelse
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
}
</script>
@endsection
