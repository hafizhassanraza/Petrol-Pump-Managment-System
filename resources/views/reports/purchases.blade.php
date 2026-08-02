@extends('layouts.app')

@section('content')

@include('reports.partials.report-styles')

<style>
    .info-card.petrol { background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%); }
    .info-card.mobil { background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%); }
</style>

<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="info-card petrol">
            <div class="info-card-label">Petroleum Purchases</div>
            <div class="info-card-value">PKR {{ money($fuelPurchaseAmount) }}</div>
            <small>{{ number_format($fuelPurchaseLiters, 2) }} L</small>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="info-card mobil">
            <div class="info-card-label">Mobil Oil Purchases</div>
            <div class="info-card-value">PKR {{ money($mobilOilPurchaseAmount) }}</div>
            <small>{{ number_format($mobilOilPurchaseQty, 2) }} units</small>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="info-card amount">
            <div class="info-card-label">Total Purchases</div>
            <div class="info-card-value">PKR {{ money($totalPurchaseAmount) }}</div>
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
            <div>
                <label for="from">From Date</label>
                <input type="date" id="from" name="from" value="{{ $from }}" class="form-control">
            </div>
            <div>
                <label for="to">To Date</label>
                <input type="date" id="to" name="to" value="{{ $to }}" class="form-control">
            </div>
            <button type="submit" class="btn-filter">Apply Filter</button>
        </div>
        <input type="hidden" id="filterInput" name="filter" value="{{ $filter }}">
    </form>
    <div class="download-section mt-3">
        <a href="{{ route('reports.purchases.pdf', ['from' => $from, 'to' => $to, 'filter' => $filter]) }}" class="btn-download btn-download-pdf">
            <i class="bi bi-file-pdf"></i> Download PDF
        </a>
        <a href="{{ route('reports.purchases.csv', ['from' => $from, 'to' => $to, 'filter' => $filter]) }}" class="btn-download btn-download-excel">
            <i class="bi bi-file-earmark-spreadsheet"></i> Download Excel
        </a>
    </div>
</div>

<div class="table-container">
    <h5 class="section-heading p-3 mb-0" style="font-size:16px;font-weight:600;color:#1e293b;">
        <i class="bi bi-fuel-pump"></i> Petroleum Purchases
    </h5>
    @if($fuelPurchases->count())
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Tank</th>
                    <th>Invoice</th>
                    <th style="text-align: right;">Qty (L)</th>
                    <th style="text-align: right;">Rate</th>
                    <th style="text-align: right;">Amount (PKR)</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($fuelPurchases as $p)
                    <tr>
                        <td>{{ optional($p->received_datetime)->format('d M Y, h:i A') }}</td>
                        <td>{{ $p->product->name ?? '—' }}</td>
                        <td>{{ $p->tank->tank_number ?? '—' }}</td>
                        <td>{{ $p->invoice_no ?: '—' }}</td>
                        <td style="text-align: right;">{{ number_format((float) $p->quantity_liters, 2) }}</td>
                        <td style="text-align: right;">{{ rate($p->purchase_rate) }}</td>
                        <td style="text-align: right; font-weight: 600;">{{ money($p->total_amount) }}</td>
                        <td>{{ $p->notes ?: '—' }}</td>
                    </tr>
                @endforeach
                <tr style="background:#f8fafc; font-weight:600;">
                    <td colspan="4">Total</td>
                    <td style="text-align: right;">{{ number_format($fuelPurchaseLiters, 2) }}</td>
                    <td></td>
                    <td style="text-align: right;">{{ money($fuelPurchaseAmount) }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @else
        <div class="empty-state"><p>No petroleum purchases for the selected date range.</p></div>
    @endif
</div>

@if($fuelByProduct->sum('count') > 0)
<div class="table-container">
    <h5 class="section-heading p-3 mb-0" style="font-size:16px;font-weight:600;color:#1e293b;">
        Petroleum by Product
    </h5>
    <table class="excel-table">
        <thead>
            <tr>
                <th>Product</th>
                <th style="text-align: right;">Qty (L)</th>
                <th style="text-align: right;">Avg Rate</th>
                <th style="text-align: right;">Amount (PKR)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($fuelByProduct as $row)
                <tr>
                    <td>{{ $row['product'] }}</td>
                    <td style="text-align: right;">{{ number_format($row['quantity'], 2) }}</td>
                    <td style="text-align: right;">{{ $row['avg_rate'] !== null ? rate($row['avg_rate']) : '—' }}</td>
                    <td style="text-align: right;">{{ money($row['amount']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="table-container">
    <h5 class="section-heading p-3 mb-0" style="font-size:16px;font-weight:600;color:#1e293b;">
        <i class="bi bi-droplet-half"></i> Mobil Oil Purchases
    </h5>
    @if($mobilOilPurchases->count())
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Invoice</th>
                    <th style="text-align: right;">Qty</th>
                    <th style="text-align: right;">Rate</th>
                    <th style="text-align: right;">Amount (PKR)</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($mobilOilPurchases as $p)
                    <tr>
                        <td>{{ optional($p->received_datetime)->format('d M Y, h:i A') }}</td>
                        <td>
                            {{ $p->product->name ?? '—' }}
                            @if($p->product?->unit)
                                <small class="text-muted">({{ $p->product->unit }})</small>
                            @endif
                        </td>
                        <td>{{ $p->invoice_no ?: '—' }}</td>
                        <td style="text-align: right;">{{ number_format((float) $p->quantity, 2) }}</td>
                        <td style="text-align: right;">{{ rate($p->purchase_rate) }}</td>
                        <td style="text-align: right; font-weight: 600;">{{ money($p->total_amount) }}</td>
                        <td>{{ $p->notes ?: '—' }}</td>
                    </tr>
                @endforeach
                <tr style="background:#f8fafc; font-weight:600;">
                    <td colspan="3">Total</td>
                    <td style="text-align: right;">{{ number_format($mobilOilPurchaseQty, 2) }}</td>
                    <td></td>
                    <td style="text-align: right;">{{ money($mobilOilPurchaseAmount) }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @else
        <div class="empty-state"><p>No mobil oil purchases for the selected date range.</p></div>
    @endif
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
