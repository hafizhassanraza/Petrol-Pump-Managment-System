@extends('layouts.app')

@section('content')
@include('partials.period-filter')

<div class="page-card">
    <div class="list-toolbar">
        <a href="{{ route('owner-fuel-usages.create') }}" class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Add Usage</a>
    </div>
    <div class="table-container">
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Person</th>
                    <th>Product</th>
                    <th>Liters</th>
                    <th>Rate</th>
                    <th>Total</th>
                    <th>Vehicle</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($usages as $u)
                    <tr>
                        <td><strong>{{ $u->person_name ?? '—' }}</strong></td>
                        <td>{{ $u->product->name ?? '—' }}</td>
                        <td>{{ number_format($u->liters, 2) }}</td>
                        <td>{{ number_format($u->price_per_liter, 2) }}</td>
                        <td>{{ number_format($u->total_amount, 2) }}</td>
                        <td>{{ $u->vehicle_no ?? '—' }}</td>
                        <td>{{ $u->usage_datetime ? \Carbon\Carbon::parse($u->usage_datetime)->format('d M Y H:i') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No records in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $usages->links() }}
</div>
@endsection
