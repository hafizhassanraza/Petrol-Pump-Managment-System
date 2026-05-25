@extends('layouts.app')

@section('content')
@include('partials.period-filter')

<div class="page-card">
    <div class="list-toolbar">
        <a href="{{ route('tank-dip-readings.create') }}" class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Add Dip Reading</a>
    </div>
    <div class="table-container">
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Tank</th>
                    <th>System (L)</th>
                    <th>Physical (L)</th>
                    <th>Difference</th>
                    <th>Reconciled</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($readings as $r)
                    <tr>
                        <td><strong>{{ $r->tank->tank_number ?? '—' }}</strong></td>
                        <td>{{ number_format($r->system_stock_liters ?? 0, 2) }}</td>
                        <td>{{ number_format($r->measured_liters, 2) }}</td>
                        <td class="{{ ($r->difference_liters ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($r->difference_liters ?? 0, 2) }}
                        </td>
                        <td>{{ $r->stock_reconciled ? 'Yes' : 'No' }}</td>
                        <td>{{ $r->reading_datetime ? \Carbon\Carbon::parse($r->reading_datetime)->format('d M Y H:i') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No dip readings in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $readings->links() }}
</div>
@endsection
