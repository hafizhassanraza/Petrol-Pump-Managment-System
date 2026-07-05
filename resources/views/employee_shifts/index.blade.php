@extends('layouts.app')

@section('content')
@include('partials.period-filter')

<div class="page-card">
    <div class="list-toolbar">
        <a href="{{ route('employee-shifts.create') }}" class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Assign Shift</a>
    </div>
    <div class="table-container">
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Nozzle</th>
                    <th>Shift</th>
                    <th>Date</th>
                    <th>Reading (Open → Close)</th>
                    <th>Liters</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shifts as $shift)
                    <tr>
                        <td>{{ $shift->employee->name ?? '—' }}</td>
                        <td>{{ $shift->nozzle->nozzle_number ?? '—' }}</td>
                        <td>{{ $shift->shift->name ?? '—' }}</td>
                        <td>{{ $shift->assigned_date }}</td>
                        <td>
                            {{ number_format($shift->opening_reading, 2) }}
                            →
                            @if($shift->closing_reading !== null)
                                {{ number_format($shift->closing_reading, 2) }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $shift->total_liters ? number_format($shift->total_liters, 2) : '—' }}</td>
                        <td>{{ $shift->total_amount ? number_format($shift->total_amount, 2) : '—' }}</td>
                        <td>
                            @if($shift->status === 'active')<span class="badge bg-primary">Active</span>
                            @elseif($shift->status === 'submitted')<span class="badge bg-warning text-dark">Submitted</span>
                            @else<span class="badge bg-success">Verified</span>@endif
                        </td>
                        <td class="text-nowrap">
                            @if(in_array($shift->status, ['active', 'submitted']))
                                <a href="{{ route('employee-shifts.edit', $shift->id) }}" class="btn btn-primary btn-sm">Edit</a>
                            @endif
                            @if($shift->status === 'active')
                                <a href="{{ route('employee-shifts.close-form', $shift->id) }}" class="btn btn-danger btn-sm">Close</a>
                            @endif
                            @if($shift->status === 'submitted')
                                <form action="{{ route('employee-shifts.verify', $shift->id) }}" method="POST" class="d-inline">@csrf
                                    <button class="btn btn-success btn-sm">Verify</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No shifts in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $shifts->links() }}
</div>
@endsection
