@extends('layouts.app')

@section('content')

<style>
    .report-header {
        margin-bottom: 30px;
        padding-top: 20px;
    }

    .report-title {
        font-size: 28px;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 25px;
    }

    /* Info Cards */
    .info-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 12px;
        padding: 20px;
        color: white;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .info-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
    }

    .info-card.amount {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .info-card.liters {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .info-card.cash {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }

    .info-card.online {
        background: linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%);
    }

    .info-card-value {
        font-size: 32px;
        font-weight: 700;
        margin-top: 10px;
    }

    .info-card-label {
        font-size: 14px;
        opacity: 0.9;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-card-icon {
        font-size: 24px;
        margin-bottom: 10px;
    }

    /* Filter Section */
    .filter-section {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        margin-bottom: 25px;
    }

    .filter-section h5 {
        color: #1e293b;
        font-weight: 600;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
    }

    .filter-section h5 i {
        margin-right: 8px;
        color: #667eea;
    }

    .filter-options {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }

    .filter-btn {
        padding: 8px 16px;
        border: 2px solid #e2e8f0;
        background: white;
        color: #475569;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .filter-btn:hover {
        border-color: #667eea;
        color: #667eea;
    }

    .filter-btn.active {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }

    .date-range-group {
        display: flex;
        gap: 10px;
        align-items: flex-end;
        flex-wrap: wrap;
        margin-bottom: 15px;
    }

    .date-input-group {
        display: flex;
        gap: 10px;
        align-items: flex-end;
        flex: 1;
        min-width: 300px;
    }

    .date-input-group label {
        color: #475569;
        font-size: 13px;
        font-weight: 500;
        margin-bottom: 5px;
        display: block;
    }

    .date-input-group input {
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        font-size: 14px;
        transition: border 0.3s ease;
    }

    .date-input-group input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .btn-filter {
        padding: 8px 20px;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 14px;
        white-space: nowrap;
    }

    .btn-filter:hover {
        background: #5568d3;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    /* Download Section */
    .download-section {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .btn-download {
        padding: 10px 16px;
        border: none;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        color: white;
    }

    .btn-download i {
        font-size: 16px;
    }

    .btn-download-pdf {
        background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);
    }

    .btn-download-pdf:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(245, 87, 108, 0.3);
    }

    .btn-download-excel {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }

    .btn-download-excel:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(17, 153, 142, 0.3);
    }

    /* Excel-like Table */
    .table-container {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        overflow-x: auto;
    }

    .excel-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .excel-table thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: 600;
    }

    .excel-table th {
        padding: 15px;
        text-align: left;
        font-weight: 600;
        border-bottom: 2px solid #667eea;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.5px;
    }

    .excel-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #e2e8f0;
        color: #475569;
    }

    .excel-table tbody tr {
        transition: background-color 0.3s ease;
    }

    .excel-table tbody tr:hover {
        background-color: #f8fafc;
    }

    .excel-table tbody tr:nth-child(even) {
        background-color: #f9fafc;
    }

    .excel-table tbody tr:nth-child(even):hover {
        background-color: #f0f4f8;
    }

    .excel-table td:first-child {
        font-weight: 500;
        color: #1e293b;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #94a3b8;
    }

    .empty-state i {
        font-size: 48px;
        margin-bottom: 15px;
        color: #cbd5e1;
    }

    .empty-state p {
        font-size: 16px;
        margin: 0;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .info-card {
            margin-bottom: 15px;
        }

        .date-input-group {
            min-width: 100%;
        }

        .filter-options {
            flex-direction: column;
        }

        .filter-btn {
            width: 100%;
            text-align: center;
        }

        .download-section {
            flex-direction: column;
        }

        .btn-download {
            width: 100%;
            justify-content: center;
        }

        .excel-table {
            font-size: 12px;
        }

        .excel-table th,
        .excel-table td {
            padding: 10px;
        }

        .report-title {
            font-size: 22px;
        }
    }
</style>

<!-- Info Cards Row -->
<div class="row mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="info-card amount">
            <div class="info-card-icon">
                <i class="bi bi-cash-coin"></i>
            </div>
            <div class="info-card-label">Total Amount</div>
            <div class="info-card-value">PKR {{ money($totalAmount) }}</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="info-card liters">
            <div class="info-card-icon">
                <i class="bi bi-fuel-pump"></i>
            </div>
            <div class="info-card-label">Total Liters</div>
            <div class="info-card-value">{{ number_format($totalLiters, 2) }} L</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="info-card cash">
            <div class="info-card-icon">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="info-card-label">Cash Received</div>
            <div class="info-card-value">PKR {{ money($totalCash) }}</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="info-card online">
            <div class="info-card-icon">
                <i class="bi bi-credit-card-2-front"></i>
            </div>
            <div class="info-card-label">Online Received</div>
            <div class="info-card-value">PKR {{ money($totalOnline) }}</div>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="filter-section">
    <h5>
        <i class="bi bi-funnel"></i> Filter Data
    </h5>

    <form method="GET" id="filterForm">
        <!-- Quick Filter Options -->
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

        <!-- Custom Date Range -->
        <div class="date-range-group" id="customDateRange" style="display: @if($filter === 'custom') flex @else none @endif;">
            <div class="date-input-group">
                <label for="from">From Date</label>
                <input type="date" id="from" name="from" value="{{ $from }}" class="form-control">
            </div>
            <div class="date-input-group">
                <label for="to">To Date</label>
                <input type="date" id="to" name="to" value="{{ $to }}" class="form-control">
            </div>
            <button type="submit" class="btn-filter">
                <i class="bi bi-search"></i> Apply Filter
            </button>
        </div>

        <input type="hidden" id="filterInput" name="filter" value="{{ $filter }}">
    </form>

    <!-- Download Buttons -->
    <div class="download-section mt-3">
        <a href="{{ route('reports.daily-sales.pdf', ['from' => $from, 'to' => $to, 'filter' => $filter]) }}" class="btn-download btn-download-pdf">
            <i class="bi bi-file-pdf"></i> Download PDF
        </a>
        <a href="{{ route('reports.daily-sales.csv', ['from' => $from, 'to' => $to, 'filter' => $filter]) }}" class="btn-download btn-download-excel">
            <i class="bi bi-file-earmark-spreadsheet"></i> Download Excel
        </a>
    </div>
</div>

<!-- Table Section -->
@include('reports.partials.product_breakdown', ['fuelBreakdownSimple' => true, 'wrapperClass' => ''])
@include('reports.partials.mobil_oil_breakdown')

@if(isset($dailyTotals) && $dailyTotals->count())
<style>
    .daily-stack-cell { line-height: 1.35; }
    .daily-stack-cell .stack-rate { font-size: 12px; color: #64748b; }
    .daily-stack-cell .stack-amount { font-size: 15px; font-weight: 700; color: #1e293b; margin: 2px 0; }
    .daily-stack-cell .stack-profit { font-size: 12px; color: #475569; }
    .daily-stack-cell .stack-stock-label { font-size: 11px; color: #64748b; margin-top: 4px; }
    .daily-stack-cell .stack-stock-value { font-size: 13px; font-weight: 600; color: #1e293b; }
    .daily-stack-cell .stack-block-label { font-size: 11px; color: #64748b; }
    .daily-stack-cell .stack-block-value { font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px; }
    .daily-total-cell .stack-amount { font-size: 15px; font-weight: 700; color: #1e293b; }
    .daily-total-cell .stack-split { font-size: 12px; color: #64748b; line-height: 1.4; }
    .excel-table td.stack-td { text-align: right; vertical-align: top; }
    .daily-info-row td {
        background: #f8fafc;
        color: #334155;
        font-size: 12px;
        text-align: left !important;
        padding-top: 8px;
        padding-bottom: 8px;
        border-top: none;
    }
    .daily-info-badge {
        display: inline-block;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        padding: 2px 6px;
        margin-right: 8px;
        border-radius: 3px;
        vertical-align: middle;
    }
    .daily-info-badge.is-refill { background: #e0f2fe; color: #0369a1; }
    .daily-info-badge.is-price { background: #fef3c7; color: #b45309; }
</style>
<div class="table-container mt-4">
    <h5 class="section-heading p-3 mb-0" style="font-size:16px;font-weight:600;color:#1e293b;">
        <i class="bi bi-calendar3"></i> Daily Breakdown
    </h5>
    <table class="excel-table">
        <thead>
            <tr>
                <th>Date</th>
                <th style="text-align: right;">Petrol</th>
                <th style="text-align: right;">Diesel</th>
                <th style="text-align: right;">Mobil Oil</th>
                <th style="text-align: right;">Total Amount</th>
                <th style="text-align: right;">Profit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dailyTotals as $day)
                <tr>
                    <td>{{ $day['label'] }}</td>
                    <td class="stack-td">@include('reports.partials.daily_stack_cell', ['row' => $day['petrol']])</td>
                    <td class="stack-td">@include('reports.partials.daily_stack_cell', ['row' => $day['diesel']])</td>
                    <td class="stack-td">@include('reports.partials.daily_stack_cell', ['row' => $day['mobil_oil']])</td>
                    <td class="stack-td">
                        <div class="daily-total-cell">
                            <div class="stack-amount">{{ money($day['total_amount']) }}</div>
                            <div class="stack-split">
                                <div>Cash {{ money($day['total_cash']) }}</div>
                                <div>Bank {{ money($day['total_online']) }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="stack-td">
                        <div class="stack-amount" style="font-weight:700;">{{ money($day['total_profit']) }}</div>
                    </td>
                </tr>
                @foreach(($day['infos'] ?? []) as $info)
                    <tr class="daily-info-row">
                        <td colspan="6">
                            <span class="daily-info-badge {{ ($info['type'] ?? '') === 'refill' ? 'is-refill' : 'is-price' }}">
                                {{ ($info['type'] ?? '') === 'refill' ? 'Stock' : 'Price' }}
                            </span>
                            {{ $info['message'] }}
                        </td>
                    </tr>
                @endforeach
            @endforeach
            @php
                $periodPetrolAmount = $dailyTotals->sum(fn ($d) => $d['petrol']['sales_amount'] ?? 0);
                $periodDieselAmount = $dailyTotals->sum(fn ($d) => $d['diesel']['sales_amount'] ?? 0);
                $periodMobilAmount = $dailyTotals->sum(fn ($d) => $d['mobil_oil']['sales_amount'] ?? 0);
                $periodPetrolProfit = $dailyTotals->sum(fn ($d) => $d['petrol']['total_profit'] ?? 0);
                $periodDieselProfit = $dailyTotals->sum(fn ($d) => $d['diesel']['total_profit'] ?? 0);
                $periodMobilProfit = $dailyTotals->sum(fn ($d) => $d['mobil_oil']['total_profit'] ?? 0);
                $lastDay = $dailyTotals->last();
            @endphp
            <tr style="background:#f8fafc; font-weight:600;">
                <td>Period Total</td>
                <td class="stack-td">
                    <div class="daily-stack-cell">
                        <div class="stack-stock-value">Close {{ number_format($lastDay['petrol']['stock_closing'] ?? 0, 2) }} L</div>
                        <div class="stack-amount">{{ money($periodPetrolAmount) }}</div>
                        <div class="stack-profit">Profit {{ money($periodPetrolProfit) }}</div>
                    </div>
                </td>
                <td class="stack-td">
                    <div class="daily-stack-cell">
                        <div class="stack-stock-value">Close {{ number_format($lastDay['diesel']['stock_closing'] ?? 0, 2) }} L</div>
                        <div class="stack-amount">{{ money($periodDieselAmount) }}</div>
                        <div class="stack-profit">Profit {{ money($periodDieselProfit) }}</div>
                    </div>
                </td>
                <td class="stack-td">
                    <div class="stack-amount">{{ money($periodMobilAmount) }}</div>
                    <div class="stack-profit" style="font-size:12px;font-weight:500;">Profit {{ money($periodMobilProfit) }}</div>
                </td>
                <td class="stack-td">
                    <div class="daily-total-cell">
                        <div class="stack-amount">{{ money($dailyTotals->sum('total_amount')) }}</div>
                        <div class="stack-split" style="font-weight:500;">
                            <div>Cash {{ money($dailyTotals->sum('total_cash')) }}</div>
                            <div>Bank {{ money($dailyTotals->sum('total_online')) }}</div>
                        </div>
                    </div>
                </td>
                <td class="stack-td">
                    <div class="stack-amount" style="font-weight:700;">{{ money($dailyTotals->sum('total_profit')) }}</div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
@elseif(
    (empty($productBreakdown['petrol']) && empty($productBreakdown['diesel']))
    && (!isset($mobilOilBreakdown) || $mobilOilBreakdown->isEmpty())
)
<div class="table-container">
    <div class="empty-state">
        <i class="bi bi-inbox"></i>
        <p>No sales data found for the selected date range.</p>
    </div>
</div>
@endif

<script>
    function setFilter(filterType) {
        document.getElementById('filterInput').value = filterType;

        if (filterType === 'custom') {
            document.getElementById('customDateRange').style.display = 'flex';
        } else {
            document.getElementById('customDateRange').style.display = 'none';
            document.getElementById('filterForm').submit();
        }

        // Update active button
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');
    }
</script>

@endsection