@extends('layouts.app')

@section('content')
@include('reports.partials.report-styles')
@include('partials.period-filter')

<div class="row mb-3">
    <div class="col-md-3 col-lg mb-2">
        <div class="info-card amount">
            <div class="info-card-label">All Activities</div>
            <div class="info-card-value">{{ number_format($summary['total']) }}</div>
        </div>
    </div>
    <div class="col-md-3 col-lg mb-2">
        <div class="info-card stock">
            <div class="info-card-label">Created</div>
            <div class="info-card-value" style="font-size:1.4rem;">{{ number_format($summary['created']) }}</div>
        </div>
    </div>
    <div class="col-md-3 col-lg mb-2">
        <div class="info-card records">
            <div class="info-card-label">Updated / Deleted</div>
            <div class="info-card-value" style="font-size:1.4rem;">{{ number_format($summary['updated'] + $summary['deleted']) }}</div>
        </div>
    </div>
    <div class="col-md-3 col-lg mb-2">
        <div class="info-card expense">
            <div class="info-card-label">Viewed / Exported</div>
            <div class="info-card-value" style="font-size:1.4rem;">{{ number_format($summary['viewed'] + $summary['exported']) }}</div>
        </div>
    </div>
    <div class="col-md-3 col-lg mb-2">
        <div class="info-card amount" style="background:linear-gradient(135deg,#64748b 0%,#334155 100%);">
            <div class="info-card-label">Login / Logout</div>
            <div class="info-card-value" style="font-size:1.4rem;">{{ number_format($summary['auth']) }}</div>
        </div>
    </div>
</div>

<div class="page-card">
    <div class="mb-3">
        <h3 class="page-title mb-1">Activity Logs</h3>
        <p class="page-subtitle mb-0">
            Tracks create, update, delete, payments, shift close/verify, report views, PDF/CSV exports, and login activity across the software.
        </p>
    </div>

    <div class="list-toolbar d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
        <form method="GET" action="{{ route('audit-logs.index') }}" class="d-flex flex-wrap gap-2 align-items-end">
            <input type="hidden" name="filter" value="{{ $filter }}">
            <input type="hidden" name="from" value="{{ $from }}">
            <input type="hidden" name="to" value="{{ $to }}">

            <div>
                <label class="form-label small mb-1">Action</label>
                <select name="action" class="form-control form-control-sm" style="min-width:140px;">
                    <option value="">All actions</option>
                    @foreach($actionLabels as $value => $label)
                        <option value="{{ $value }}" @selected($actionFilter === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label small mb-1">Module</label>
                <select name="module" class="form-control form-control-sm" style="min-width:180px;">
                    <option value="">All modules</option>
                    @foreach($modules as $module)
                        <option value="{{ $module }}" @selected($moduleFilter === $module)>
                            {{ \App\Models\AuditLog::moduleLabel($module) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label small mb-1">User</label>
                <select name="user_id" class="form-control form-control-sm" style="min-width:160px;">
                    <option value="">All users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((int) $userFilter === (int) $user->id)>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label small mb-1">Search</label>
                <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm"
                       placeholder="Description, module, IP…" style="min-width:200px;">
            </div>

            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-search"></i> Apply
            </button>
        </form>

        <a href="{{ route('audit-logs.pdf', request()->query()) }}" class="btn btn-danger btn-sm">
            <i class="bi bi-file-pdf"></i> Download PDF
        </a>
    </div>

    <div class="table-container">
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Date / Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Activity</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="text-nowrap">{{ $log->created_at?->format('d M Y H:i:s') }}</td>
                        <td>{{ $log->user?->name ?? 'System' }}</td>
                        <td>
                            <span class="badge
                                @if($log->action === 'created') bg-success
                                @elseif($log->action === 'updated') bg-primary
                                @elseif($log->action === 'deleted') bg-danger
                                @elseif($log->action === 'paid') bg-info text-dark
                                @elseif($log->action === 'exported') bg-dark
                                @elseif($log->action === 'viewed') bg-light text-dark border
                                @elseif(in_array($log->action, ['closed', 'verified'], true)) bg-warning text-dark
                                @elseif(in_array($log->action, ['logged_in', 'logged_out'], true)) bg-secondary
                                @else bg-dark
                                @endif">
                                {{ $log->actionLabel() }}
                            </span>
                        </td>
                        <td>{{ $log->moduleDisplay() }}</td>
                        <td>
                            <div>{{ $log->description }}</div>
                            @if($log->method || $log->route_name)
                                <small class="text-muted">
                                    {{ $log->method ?: '' }}
                                    @if($log->method && $log->route_name) · @endif
                                    {{ $log->route_name ?: '' }}
                                </small>
                            @endif
                        </td>
                        <td>{{ $log->ip_address ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No activity found for this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($logs, 'links'))
        <div class="mt-3">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
