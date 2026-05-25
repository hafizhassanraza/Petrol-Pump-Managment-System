@extends('layouts.app')

@section('content')

@include('reports.partials.report-styles')

<div class="row mb-4">
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card amount">
            <div class="info-card-icon"><i class="bi bi-cash-coin"></i></div>
            <div class="info-card-label">Total Sales</div>
            <div class="info-card-value">PKR {{ number_format($sales, 2) }}</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card secondary">
            <div class="info-card-icon"><i class="bi bi-wallet2"></i></div>
            <div class="info-card-label">Total Costs</div>
            <div class="info-card-value">PKR {{ number_format($totalCosts, 2) }}</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card {{ $netProfit >= 0 ? 'success' : 'danger' }}">
            <div class="info-card-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="info-card-label">Net Profit</div>
            <div class="info-card-value">PKR {{ number_format($netProfit, 2) }}</div>
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

<div class="filter-section">
    <h5 class="section-heading"><i class="bi bi-clipboard-data"></i> P&amp;L Summary</h5>
    <div class="pl-line">
        <span>Total Sales <small class="text-muted">({{ $salesCount }} shifts, {{ number_format($salesLiters, 2) }} L)</small></span>
        <strong class="text-profit">PKR {{ number_format($sales, 2) }}</strong>
    </div>
    <div class="pl-line">
        <span>Operating Expenses <small class="text-muted">({{ $expenseCount }} entries, {{ $expenseRatio }}% of sales)</small></span>
        <strong class="text-loss">- PKR {{ number_format($expenses, 2) }}</strong>
    </div>
    <div class="pl-line">
        <span>Owner Fuel Usage <small class="text-muted">({{ $ownerFuelCount }} entries, {{ number_format($ownerFuelLiters, 2) }} L, {{ $ownerFuelRatio }}% of sales)</small></span>
        <strong class="text-loss">- PKR {{ number_format($ownerFuel, 2) }}</strong>
    </div>
    <div class="pl-line">
        <span>Tank Refill COGS <small class="text-muted">({{ number_format($refillLiters, 2) }} L purchased)</small></span>
        <strong class="text-loss">- PKR {{ number_format($refillCogs, 2) }}</strong>
    </div>
    <div class="pl-line">
        <span>Total Costs (incl. COGS)</span>
        <strong class="text-loss">- PKR {{ number_format($totalCosts, 2) }}</strong>
    </div>
    <div class="pl-line" style="background:#f0fdf4;">
        <span>Gross Profit <small class="text-muted">(before refill purchases)</small></span>
        <strong class="{{ $grossProfit >= 0 ? 'text-profit' : 'text-loss' }}">PKR {{ number_format($grossProfit, 2) }}</strong>
    </div>
    <div class="pl-line total">
        <span>Net Profit / Loss <small class="text-muted">(after COGS)</small></span>
        <strong class="{{ $netProfit >= 0 ? 'text-profit' : 'text-loss' }}">PKR {{ number_format($netProfit, 2) }}</strong>
    </div>
</div>

@if($expenseByType->count() > 0)
<div class="table-container">
    <h5 class="section-heading p-3 mb-0"><i class="bi bi-tags"></i> Expense Breakdown by Type</h5>
    <table class="excel-table">
        <thead>
            <tr>
                <th>Expense Type</th>
                <th style="text-align: right;">Amount (PKR)</th>
                <th style="text-align: right;">% of Sales</th>
                <th style="text-align: right;">Entries</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expenseByType as $row)
                <tr>
                    <td>{{ $row->expense_type }}</td>
                    <td style="text-align: right; font-weight: 600; color: #667eea;">{{ number_format($row->total, 2) }}</td>
                    <td style="text-align: right;">{{ $sales > 0 ? number_format(($row->total / $sales) * 100, 1) : 0 }}%</td>
                    <td style="text-align: right;">{{ $row->count }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="table-container">
    <h5 class="section-heading p-3 mb-0"><i class="bi bi-calendar3"></i> Daily Breakdown</h5>
    @if($dailyBreakdown->count() > 0)
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th style="text-align: right;">Sales</th>
                    <th style="text-align: right;">Expenses</th>
                    <th style="text-align: right;">Owner Fuel</th>
                    <th style="text-align: right;">Total Costs</th>
                    <th style="text-align: right;">Net Profit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dailyBreakdown as $day)
                    <tr>
                        <td>{{ $day['label'] }}</td>
                        <td style="text-align: right;">{{ number_format($day['sales'], 2) }}</td>
                        <td style="text-align: right;">{{ number_format($day['expenses'], 2) }}</td>
                        <td style="text-align: right;">{{ number_format($day['owner_fuel'], 2) }}</td>
                        <td style="text-align: right;">{{ number_format($day['costs'], 2) }}</td>
                        <td style="text-align: right; font-weight: 600;" class="{{ $day['net'] >= 0 ? 'text-profit' : 'text-loss' }}">
                            {{ number_format($day['net'], 2) }}
                        </td>
                    </tr>
                @endforeach
                <tr style="background: #f0f4f8;">
                    <td><strong>Period Total</strong></td>
                    <td style="text-align: right;"><strong>{{ number_format($sales, 2) }}</strong></td>
                    <td style="text-align: right;"><strong>{{ number_format($expenses, 2) }}</strong></td>
                    <td style="text-align: right;"><strong>{{ number_format($ownerFuel, 2) }}</strong></td>
                    <td style="text-align: right;"><strong>{{ number_format($totalCosts, 2) }}</strong></td>
                    <td style="text-align: right;"><strong class="{{ $netProfit >= 0 ? 'text-profit' : 'text-loss' }}">{{ number_format($netProfit, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    @else
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <p>No financial activity found for the selected period.</p>
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
