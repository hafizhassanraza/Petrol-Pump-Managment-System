@extends('layouts.app')

@section('content')

<div class="filter-section">
    <h5><i class="bi bi-funnel"></i> Filter</h5>
    <form method="GET" action="{{ request()->url() }}" id="periodFilterForm">
        <div class="filter-options">
            <button type="button"
                    class="filter-btn @if(($statusFilter ?? 'open') === 'open') active @endif"
                    onclick="setShiftFilter('open')">
                <i class="bi bi-unlock"></i> All Open
            </button>
            <button type="button"
                    class="filter-btn @if(($statusFilter ?? '') === 'all' && ($filter ?? '') === 'today') active @endif"
                    onclick="setShiftFilter('today')">
                <i class="bi bi-calendar-check"></i> Today
            </button>
            <button type="button"
                    class="filter-btn @if(($statusFilter ?? '') === 'all' && ($filter ?? '') === 'last-week') active @endif"
                    onclick="setShiftFilter('last-week')">
                <i class="bi bi-calendar-week"></i> Last 7 Days
            </button>
            <button type="button"
                    class="filter-btn @if(($statusFilter ?? '') === 'all' && ($filter ?? '') === 'last-month') active @endif"
                    onclick="setShiftFilter('last-month')">
                <i class="bi bi-calendar-month"></i> Last 30 Days
            </button>
            <button type="button"
                    class="filter-btn @if(($statusFilter ?? '') === 'all' && ($filter ?? '') === 'custom') active @endif"
                    onclick="setShiftFilter('custom')">
                <i class="bi bi-calendar-range"></i> Custom
            </button>
        </div>
        <div class="date-range-group" id="customDateRange" style="display: @if(($filter ?? '') === 'custom' && ($statusFilter ?? '') === 'all') flex @else none @endif;">
            <div class="date-input-group">
                <label for="from">From</label>
                <input type="date" id="from" name="from" value="{{ $from ?? '' }}" class="form-control">
            </div>
            <div class="date-input-group">
                <label for="to">To</label>
                <input type="date" id="to" name="to" value="{{ $to ?? '' }}" class="form-control">
            </div>
            <button type="submit" class="btn-filter"><i class="bi bi-search"></i> Apply</button>
        </div>
        <input type="hidden" id="filterInput" name="filter" value="{{ ($statusFilter ?? 'open') === 'open' ? 'open' : ($filter ?? 'today') }}">
        <input type="hidden" id="statusInput" name="status" value="{{ $statusFilter ?? 'open' }}">
    </form>
</div>

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
                    <th>Opening Date</th>
                    <th>Closing Date</th>
                    <th>Reading (Open → Close)</th>
                    <th>Liters</th>
                    <th>Amount</th>
                    <th>Cash</th>
                    <th>Online</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shifts as $shift)
                    <tr>
                        <td>{{ $shift->employee->name ?? '—' }}</td>
                        <td>{{ $shift->nozzle->nozzle_number ?? '—' }}</td>
                        <td>{{ $shift->assigned_date?->format('d M Y') ?? '—' }}</td>
                        <td>{{ $shift->closed_date?->format('d M Y') ?? '—' }}</td>
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
                        <td>{{ $shift->total_amount ? money($shift->total_amount) : '—' }}</td>
                        <td>{{ $shift->status === 'active' ? '—' : money($shift->cash_received) }}</td>
                        <td>{{ $shift->status === 'active' ? '—' : money($shift->online_received) }}</td>
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
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">
                            @if(($statusFilter ?? 'open') === 'open')
                                No open shifts found.
                            @else
                                No shifts in this period.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $shifts->links() }}
</div>

@push('scripts')
<script>
function setShiftFilter(type) {
    const filterInput = document.getElementById('filterInput');
    const statusInput = document.getElementById('statusInput');
    const customRange = document.getElementById('customDateRange');

    if (type === 'open') {
        filterInput.value = 'open';
        statusInput.value = 'open';
        customRange.style.display = 'none';
        document.getElementById('periodFilterForm').submit();
        return;
    }

    statusInput.value = 'all';
    filterInput.value = type;

    if (type === 'custom') {
        customRange.style.display = 'flex';
    } else {
        customRange.style.display = 'none';
        document.getElementById('periodFilterForm').submit();
    }

    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    if (event && event.target) {
        (event.target.closest('.filter-btn') || event.target).classList.add('active');
    }
}
</script>
@endpush

@endsection
