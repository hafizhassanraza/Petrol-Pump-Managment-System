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

    .info-card.records {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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

<div class="row mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="info-card amount">
            <div class="info-card-icon">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="info-card-label">Total Amount</div>
            <div class="info-card-value">PKR {{ number_format($totalAmount, 2) }}</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="info-card records">
            <div class="info-card-icon">
                <i class="bi bi-journal-text"></i>
            </div>
            <div class="info-card-label">Total Records</div>
            <div class="info-card-value">{{ number_format($totalRecords) }}</div>
        </div>
    </div>
</div>

<div class="filter-section">
    <h5>
        <i class="bi bi-funnel"></i> Filter Data
    </h5>

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
            <button type="submit" class="btn-filter">
                <i class="bi bi-search"></i> Apply Filter
            </button>
        </div>

        <input type="hidden" id="filterInput" name="filter" value="{{ $filter }}">
    </form>

    <div class="download-section mt-3">
        <a href="{{ route('reports.expenses.pdf', ['from' => $from, 'to' => $to, 'filter' => $filter]) }}" class="btn-download btn-download-pdf">
            <i class="bi bi-file-pdf"></i> Download PDF
        </a>
        <a href="{{ route('reports.expenses.csv', ['from' => $from, 'to' => $to, 'filter' => $filter]) }}" class="btn-download btn-download-excel">
            <i class="bi bi-file-earmark-spreadsheet"></i> Download Excel
        </a>
    </div>
</div>

<div class="table-container">
    @if($expenses->count() > 0)
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th style="text-align: right;">Amount (PKR)</th>
                    <th>Date</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expenses as $e)
                    <tr>
                        <td><span style="background: #f0f4f8; padding: 4px 8px; border-radius: 4px;">{{ $e->expense_type }}</span></td>
                        <td style="text-align: right; font-weight: 600; color: #667eea;">{{ number_format($e->amount, 2) }}</td>
                        <td>{{ \Carbon\Carbon::parse($e->expense_date)->format('d M Y') }}</td>
                        <td>{{ $e->notes ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <p>No expense data found for the selected date range.</p>
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

        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');
    }
</script>

@endsection
