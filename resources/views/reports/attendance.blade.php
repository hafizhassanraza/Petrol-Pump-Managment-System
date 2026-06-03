@extends('layouts.app')

@section('content')
@include('reports.partials.report-styles')

<div class="report-header mb-3">
    <a href="{{ route('reports.dashboard') }}" class="btn btn-secondary btn-sm mb-2"><i class="bi bi-arrow-left"></i> Reports</a>
    <h2 class="report-title">Employee Attendance Report</h2>
</div>

<div class="row mb-4">
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card records">
            <div class="info-card-icon"><i class="bi bi-journal-check"></i></div>
            <div class="info-card-label">Total Records</div>
            <div class="info-card-value">{{ number_format($totalRecords) }}</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card amount">
            <div class="info-card-icon"><i class="bi bi-person-check"></i></div>
            <div class="info-card-label">Present</div>
            <div class="info-card-value">{{ $statusCounts['present'] ?? 0 }}</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card expense">
            <div class="info-card-icon"><i class="bi bi-person-x"></i></div>
            <div class="info-card-label">Absent</div>
            <div class="info-card-value">{{ $statusCounts['absent'] ?? 0 }}</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card stock">
            <div class="info-card-icon"><i class="bi bi-clock-history"></i></div>
            <div class="info-card-label">Late</div>
            <div class="info-card-value">{{ $statusCounts['late'] ?? 0 }}</div>
        </div>
    </div>
</div>

<div class="filter-section">
    <h5><i class="bi bi-funnel"></i> Filter Data</h5>
    <form method="GET" id="filterForm">
        <div class="filter-options">
            <button type="button" class="filter-btn @if($filter === 'today') active @endif" onclick="setFilter('today')">Today</button>
            <button type="button" class="filter-btn @if($filter === 'last-week') active @endif" onclick="setFilter('last-week')">Last 7 Days</button>
            <button type="button" class="filter-btn @if($filter === 'last-month') active @endif" onclick="setFilter('last-month')">Last 30 Days</button>
            <button type="button" class="filter-btn @if($filter === 'custom') active @endif" onclick="setFilter('custom')">Custom</button>
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
        <a href="{{ route('reports.attendance.pdf', ['from' => $from, 'to' => $to, 'filter' => $filter]) }}" class="btn-download btn-download-pdf"><i class="bi bi-file-pdf"></i> PDF</a>
        <a href="{{ route('reports.attendance.csv', ['from' => $from, 'to' => $to, 'filter' => $filter]) }}" class="btn-download btn-download-excel"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</a>
        <a href="{{ route('employee-attendances.create') }}" class="btn-download" style="background:#16a34a;"><i class="bi bi-plus-lg"></i> Mark Attendance</a>
    </div>
</div>

<h5 class="mb-3">By Employee</h5>
<div class="table-container mb-4">
    @if($employeeSummaries->count())
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Code</th>
                    <th>Present</th>
                    <th>Absent</th>
                    <th>Late</th>
                    <th>Half Day</th>
                    <th>On Leave</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employeeSummaries as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['employee_code'] }}</td>
                        <td>{{ $row['present'] }}</td>
                        <td>{{ $row['absent'] }}</td>
                        <td>{{ $row['late'] }}</td>
                        <td>{{ $row['half_day'] }}</td>
                        <td>{{ $row['on_leave'] }}</td>
                        <td><strong>{{ $row['total'] }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state"><p>No attendance data for this period.</p></div>
    @endif
</div>

<h5 class="mb-3">Daily Records</h5>
<div class="table-container mb-4">
    @if($attendances->count())
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Employee</th>
                    <th>Status</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Hours</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendances as $a)
                    <tr>
                        <td>{{ $a->attendance_date->format('d M Y') }}</td>
                        <td>{{ $a->employee->name ?? '—' }}</td>
                        <td>{{ $a->status_label }}</td>
                        <td>{{ $a->check_in ? \Carbon\Carbon::parse($a->check_in)->format('h:i A') : '—' }}</td>
                        <td>{{ $a->check_out ? \Carbon\Carbon::parse($a->check_out)->format('h:i A') : '—' }}</td>
                        <td>{{ $a->worked_hours !== null ? number_format($a->worked_hours, 2) : '—' }}</td>
                        <td>{{ $a->notes ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state"><p>No attendance records found.</p></div>
    @endif
</div>

@if($dailyTotals->count())
<h5 class="mb-3">Daily Summary</h5>
<div class="table-container">
    <table class="excel-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Present</th>
                <th>Absent</th>
                <th>Late</th>
                <th>Half Day</th>
                <th>On Leave</th>
                <th>Records</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dailyTotals as $day)
                <tr>
                    <td>{{ $day['label'] }}</td>
                    <td>{{ $day['present'] }}</td>
                    <td>{{ $day['absent'] }}</td>
                    <td>{{ $day['late'] }}</td>
                    <td>{{ $day['half_day'] }}</td>
                    <td>{{ $day['on_leave'] }}</td>
                    <td>{{ $day['record_count'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
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
    document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
    if (event && event.target) event.target.classList.add('active');
}
</script>
@endsection
