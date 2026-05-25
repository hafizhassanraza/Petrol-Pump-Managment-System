@extends('layouts.app')

@section('content')
<div class="page-card">
    <div class="list-toolbar">
        <a href="{{ route('nozzles.create') }}" class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Add Nozzle</a>
    </div>
    <div class="table-container">
        <table class="excel-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nozzle</th>
                    <th>Dispenser</th>
                    <th>Tank</th>
                    <th>Product</th>
                    <th>Meter</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($nozzles as $nozzle)
                    <tr>
                        <td>{{ $nozzle->id }}</td>
                        <td><strong>{{ $nozzle->nozzle_number }}</strong></td>
                        <td>{{ $nozzle->dispenser->dispenser_code ?? '—' }}</td>
                        <td>{{ $nozzle->tank->tank_number ?? '—' }}</td>
                        <td>{{ $nozzle->product->name ?? '—' }}</td>
                        <td>{{ number_format($nozzle->current_meter_reading, 2) }}</td>
                        <td>
                            @if($nozzle->status)<span class="status-active">Active</span>
                            @else<span class="status-inactive">Inactive</span>@endif
                        </td>
                        <td>
                            <a href="{{ route('nozzles.edit', $nozzle) }}" class="btn btn-warning btn-sm">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No nozzles found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $nozzles->links() }}
</div>
@endsection
