@extends('layouts.app')

@section('content')

@include('reports.partials.report-styles')

<style>
    .info-card.liters { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .info-card.cash { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
    .info-card.online { background: linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%); }
</style>

<div class="row mb-4">
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card records">
            <div class="info-card-label">Shifts</div>
            <div class="info-card-value">{{ number_format($totalShifts) }}</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card liters">
            <div class="info-card-label">Total Liters</div>
            <div class="info-card-value">{{ number_format($totalLiters, 2) }} L</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card amount">
            <div class="info-card-label">Total Amount</div>
            <div class="info-card-value">PKR {{ money($totalAmount) }}</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card cash">
            <div class="info-card-label">Cash / Bank</div>
            <div class="info-card-value" style="font-size:20px;">
                {{ money($totalCash) }} / {{ money($totalOnline) }}
            </div>
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
        <a href="{{ route('reports.shifts.pdf', ['from' => $from, 'to' => $to, 'filter' => $filter]) }}" class="btn-download btn-download-pdf">
            <i class="bi bi-file-pdf"></i> Download PDF
        </a>
        <a href="{{ route('reports.shifts.csv', ['from' => $from, 'to' => $to, 'filter' => $filter]) }}" class="btn-download btn-download-excel">
            <i class="bi bi-file-earmark-spreadsheet"></i> Download Excel
        </a>
    </div>
</div>

<div class="table-container">
    @if($shifts->count())
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Employee</th>
                    <th>Nozzle</th>
                    <th>Fuel</th>
                    <th style="text-align: right;">Opening Meter</th>
                    <th style="text-align: right;">Closing Meter</th>
                    <th style="text-align: right;">Closing Stock</th>
                    <th style="text-align: right;">Testing (L)</th>
                    <th style="text-align: right;">Liters</th>
                    <th style="text-align: right;">Rate</th>
                    <th style="text-align: right;">Amount</th>
                    <th style="text-align: right;">Cash</th>
                    <th style="text-align: right;">Bank</th>
                    <th style="text-align: right;">Shortage</th>
                    <th style="text-align: right;">Extra</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($shifts as $s)
                    @php
                        $isOpen = $s->status === 'active';
                        $dateKey = \Carbon\Carbon::parse($s->closed_date ?? $s->assigned_date)->format('Y-m-d');
                        $closing = $closingByDay->get($dateKey, [
                            'petrol' => ['stock_closing' => 0.0],
                            'diesel' => ['stock_closing' => 0.0],
                        ]);
                    @endphp
                    <tr>
                        <td>{{ report_date($s->closed_date ?? $s->assigned_date) }}</td>
                        <td>{{ $s->employee->name ?? '—' }}</td>
                        <td>{{ $s->nozzle->nozzle_number ?? '—' }}</td>
                        <td>{{ $s->nozzle->product->name ?? '—' }}</td>
                        <td style="text-align: right;">{{ $s->opening_reading !== null ? number_format((float) $s->opening_reading, 2) : '—' }}</td>
                        <td style="text-align: right;">{{ $s->closing_reading !== null ? number_format((float) $s->closing_reading, 2) : '—' }}</td>
                        <td style="text-align: right; font-size: 12px; line-height: 1.35;">
                            <div>Petrol {{ number_format($closing['petrol']['stock_closing'] ?? 0, 2) }} L</div>
                            <div>Diesel {{ number_format($closing['diesel']['stock_closing'] ?? 0, 2) }} L</div>
                        </td>
                        <td style="text-align: right;">{{ $isOpen ? '—' : number_format((float) ($s->testing_liters ?? 0), 2) }}</td>
                        <td style="text-align: right; font-weight: 600;">{{ $isOpen ? '—' : number_format((float) ($s->total_liters ?? 0), 2) }}</td>
                        <td style="text-align: right;">{{ $isOpen || $s->price_per_liter === null ? '—' : rate($s->price_per_liter) }}</td>
                        <td style="text-align: right; font-weight: 600;">{{ $isOpen ? '—' : money($s->total_amount) }}</td>
                        <td style="text-align: right;">{{ $isOpen ? '—' : money($s->cash_received) }}</td>
                        <td style="text-align: right;">{{ $isOpen ? '—' : money($s->online_received) }}</td>
                        <td style="text-align: right;">{{ $isOpen ? '—' : money($s->shortage_amount) }}</td>
                        <td style="text-align: right;">{{ $isOpen ? '—' : money($s->extra_amount) }}</td>
                        <td>{{ ucfirst((string) $s->status) }}</td>
                    </tr>
                @endforeach
                <tr style="background:#f8fafc; font-weight:600;">
                    <td colspan="8">Grand Total (closed shifts)</td>
                    <td style="text-align: right;">{{ number_format($totalLiters, 2) }}</td>
                    <td></td>
                    <td style="text-align: right;">{{ money($totalAmount) }}</td>
                    <td style="text-align: right;">{{ money($totalCash) }}</td>
                    <td style="text-align: right;">{{ money($totalOnline) }}</td>
                    <td style="text-align: right;">{{ money($totalShortage) }}</td>
                    <td style="text-align: right;">{{ money($totalExtra) }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @else
        <div class="empty-state">
            <p>No shifts found for the selected date range.</p>
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
