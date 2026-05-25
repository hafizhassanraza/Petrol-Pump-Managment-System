@extends('layouts.app')

@section('content')
<div class="page-card">
    <div class="list-toolbar">
        <a href="{{ route('tanks.create') }}" class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Add Tank</a>
    </div>
    <div class="table-container">
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Tank</th>
                    <th>Product</th>
                    <th>Capacity (L)</th>
                    <th>Stock (L)</th>
                    <th>Min Level</th>
                    <th>Fill %</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tanks as $t)
                    @php $fill = $t->capacity_liters > 0 ? round(($t->current_stock_liters / $t->capacity_liters) * 100, 1) : 0; @endphp
                    <tr>
                        <td><strong>{{ $t->tank_number }}</strong></td>
                        <td>{{ $t->product->name ?? '—' }}</td>
                        <td>{{ number_format($t->capacity_liters, 2) }}</td>
                        <td>{{ number_format($t->current_stock_liters, 2) }}</td>
                        <td>{{ number_format($t->minimum_level, 2) }}</td>
                        <td>{{ $fill }}%</td>
                        <td>@if($t->status)<span class="status-active">Active</span>@else<span class="status-inactive">Inactive</span>@endif</td>
                        <td><a href="{{ route('tanks.edit', $t) }}" class="btn btn-warning btn-sm">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No tanks found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $tanks->links() }}
</div>
@endsection
