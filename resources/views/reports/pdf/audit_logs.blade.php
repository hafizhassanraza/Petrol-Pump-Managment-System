@extends('reports.pdf.layout')

@section('title')
Activity Logs
@endsection

@section('report-meta')
<strong>Range:</strong> {{ report_date($from) }} &mdash; {{ report_date($to) }}
· <strong>Total:</strong> {{ number_format($summary['total'] ?? $logs->count()) }}
@if(!empty($actionFilter)) · <strong>Action:</strong> {{ $actionLabels[$actionFilter] ?? $actionFilter }} @endif
@if(!empty($moduleFilter)) · <strong>Module:</strong> {{ \App\Models\AuditLog::moduleLabel($moduleFilter) }} @endif
@if(!empty($search)) · <strong>Search:</strong> {{ $search }} @endif
@endsection

@section('content')

<style>
    table { font-size: 10px; }
    table td.wrap { word-wrap: break-word; max-width: 280px; }
</style>

@if($logs->count())
    <table>
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
            @foreach($logs as $log)
                <tr>
                    <td>{{ $log->created_at?->format('d M Y H:i:s') }}</td>
                    <td>{{ $log->user?->name ?? 'System' }}</td>
                    <td>{{ $log->actionLabel() }}</td>
                    <td>{{ $log->moduleDisplay() }}</td>
                    <td class="wrap">{{ $log->description }}</td>
                    <td>{{ $log->ip_address ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p style="margin-top:10px;font-size:10px;color:#64748b;">
        Showing {{ $logs->count() }} activit{{ $logs->count() === 1 ? 'y' : 'ies' }} (max 2000 for PDF export).
    </p>
@else
    <p>No activity found for this period.</p>
@endif

@endsection
