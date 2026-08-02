@extends('layouts.app')

@section('content')

@include('reports.partials.report-styles')

<style>
    .info-card.cash { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
    .info-card.online { background: linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%); }
    .info-card.out { background: linear-gradient(135deg, #ef4444 0%, #f97316 100%); }
    .info-card.balance { background: linear-gradient(135deg, #0f172a 0%, #334155 100%); }
    .excel-table th, .excel-table td { white-space: nowrap; }
    .excel-table td.num { text-align: right; }
</style>

<div class="row mb-4">
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card cash">
            <div class="info-card-label">Sales Cash</div>
            <div class="info-card-value">PKR {{ money($total_sales_cash) }}</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card amount">
            <div class="info-card-label">Cash In</div>
            <div class="info-card-value">PKR {{ money($total_cash_in) }}</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card out">
            <div class="info-card-label">Cash Out + Expenses</div>
            <div class="info-card-value">PKR {{ money($total_cash_out + $total_expenses) }}</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card online">
            <div class="info-card-label">Sales Bank</div>
            <div class="info-card-value">PKR {{ money($total_sales_bank) }}</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card balance">
            <div class="info-card-label">Closing Balance</div>
            <div class="info-card-value">PKR {{ money($closing_balance) }}</div>
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
        <a href="{{ route('reports.cash.pdf', ['from' => $from, 'to' => $to, 'filter' => $filter]) }}" class="btn-download btn-download-pdf">
            <i class="bi bi-file-pdf"></i> Download PDF
        </a>
        <a href="{{ route('reports.cash.csv', ['from' => $from, 'to' => $to, 'filter' => $filter]) }}" class="btn-download btn-download-excel">
            <i class="bi bi-file-earmark-spreadsheet"></i> Download Excel
        </a>
    </div>
</div>

<div class="table-container">
    <h5 class="section-heading p-3 mb-0" style="font-size:16px;font-weight:600;color:#1e293b;">
        <i class="bi bi-calendar3"></i> Daily Cash Ledger
    </h5>
    @if($days->count())
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th style="text-align: right;">Sales Cash</th>
                    <th style="text-align: right;">Sales Bank</th>
                    <th style="text-align: right;">Cash In</th>
                    <th style="text-align: right;">Cash Out</th>
                    <th style="text-align: right;">Expenses</th>
                    <th style="text-align: right;">Closing</th>
                </tr>
            </thead>
            <tbody>
                @foreach($days as $day)
                    <tr>
                        <td>{{ $day['label'] }}</td>
                        <td class="num">{{ money($day['sales_cash']) }}</td>
                        <td class="num">{{ money($day['sales_bank']) }}</td>
                        <td class="num">{{ money($day['cash_in']) }}</td>
                        <td class="num">{{ money($day['cash_out']) }}</td>
                        <td class="num">{{ money($day['expenses']) }}</td>
                        <td class="num" style="font-weight:700;">{{ money($day['closing']) }}</td>
                    </tr>
                @endforeach
                <tr style="background:#f8fafc; font-weight:600;">
                    <td>Period Total</td>
                    <td class="num">{{ money($total_sales_cash) }}</td>
                    <td class="num">{{ money($total_sales_bank) }}</td>
                    <td class="num">{{ money($total_cash_in) }}</td>
                    <td class="num">{{ money($total_cash_out) }}</td>
                    <td class="num">{{ money($total_expenses) }}</td>
                    <td class="num">{{ money($closing_balance) }}</td>
                </tr>
            </tbody>
        </table>
    @else
        <div class="empty-state">
            <p>No cash activity found for the selected date range.</p>
        </div>
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
