@extends('reports.pdf.layout')

@section('title')
Employee Attendance Report
@endsection

@section('content')
<div class="range-info">
    <strong>Range:</strong> {{ \Carbon\Carbon::parse($from)->format('d M Y') }} &mdash; {{ \Carbon\Carbon::parse($to)->format('d M Y') }}<br>
    <strong>Records:</strong> {{ $totalRecords }} &nbsp;|&nbsp;
    <strong>Present:</strong> {{ $statusCounts['present'] ?? 0 }} &nbsp;|&nbsp;
    <strong>Absent:</strong> {{ $statusCounts['absent'] ?? 0 }} &nbsp;|&nbsp;
    <strong>Late:</strong> {{ $statusCounts['late'] ?? 0 }}
</div>

<h3 style="margin-top: 16px;">Employee Summary</h3>
<table>
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
                <td>{{ $row['total'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<h3 style="margin-top: 16px;">Attendance Records</h3>
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Employee</th>
            <th>Status</th>
            <th>In</th>
            <th>Out</th>
            <th>Hours</th>
        </tr>
    </thead>
    <tbody>
        @foreach($attendances as $a)
            <tr>
                <td>{{ $a->attendance_date->format('d-m-Y') }}</td>
                <td>{{ $a->employee->name ?? '' }}</td>
                <td>{{ $a->status_label }}</td>
                <td>{{ $a->check_in ? \Carbon\Carbon::parse($a->check_in)->format('H:i') : '' }}</td>
                <td>{{ $a->check_out ? \Carbon\Carbon::parse($a->check_out)->format('H:i') : '' }}</td>
                <td>{{ $a->worked_hours !== null ? number_format($a->worked_hours, 2) : '' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endsection
