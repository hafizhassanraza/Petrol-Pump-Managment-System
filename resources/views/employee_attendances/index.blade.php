@extends('layouts.app')

@section('content')
@include('partials.period-filter', ['formAction' => route('employee-attendances.index')])

<div class="page-card">
    <div class="list-toolbar">
        <a href="{{ route('employee-attendances.create') }}" class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Mark Attendance</a>
        <a href="{{ route('reports.attendance') }}" class="btn btn-primary btn-sm"><i class="bi bi-bar-chart-line"></i> Attendance Report</a>
    </div>
    <div class="table-container">
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Employee</th>
                    <th>Code</th>
                    <th>Status</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Hours</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $a)
                    <tr>
                        <td>{{ $a->attendance_date->format('d M Y') }}</td>
                        <td><strong>{{ $a->employee->name ?? '—' }}</strong></td>
                        <td>{{ $a->employee->employee_code ?? '—' }}</td>
                        <td>
                            @php
                                $badge = match($a->status) {
                                    'present' => 'status-active',
                                    'absent' => 'status-inactive',
                                    'late' => 'text-warning fw-semibold',
                                    'half_day' => 'text-primary fw-semibold',
                                    'on_leave' => 'text-muted fw-semibold',
                                    default => '',
                                };
                            @endphp
                            <span class="{{ $badge }}">{{ $a->status_label }}</span>
                        </td>
                        <td>{{ $a->check_in ? \Carbon\Carbon::parse($a->check_in)->format('h:i A') : '—' }}</td>
                        <td>{{ $a->check_out ? \Carbon\Carbon::parse($a->check_out)->format('h:i A') : '—' }}</td>
                        <td>{{ $a->worked_hours !== null ? number_format($a->worked_hours, 2) . ' h' : '—' }}</td>
                        <td>{{ $a->notes ?: '—' }}</td>
                        <td>
                            <a href="{{ route('employee-attendances.edit', $a) }}" class="btn btn-warning btn-sm">Edit</a>
                            <form action="{{ route('employee-attendances.destroy', $a) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this attendance record?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No attendance records in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $attendances->links() }}
</div>
@endsection
