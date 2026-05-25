@extends('layouts.app')

@section('content')
@include('partials.period-filter')

<div class="page-card">
    <div class="list-toolbar">
        <a href="{{ route('tank-refills.create') }}" class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Add Refill</a>
    </div>
    <div class="table-container">
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Tank</th>
                    <th>Product</th>
                    <th>Invoice</th>
                    <th>Qty (L)</th>
                    <th>Rate</th>
                    <th>Total</th>
                    <th>Received</th>
                </tr>
            </thead>
            <tbody>
                @forelse($refills as $r)
                    <tr>
                        <td>{{ $r->tank->tank_number ?? '—' }}</td>
                        <td>{{ $r->product->name ?? '—' }}</td>
                        <td>{{ $r->invoice_no ?? '—' }}</td>
                        <td>{{ number_format($r->quantity_liters, 2) }}</td>
                        <td>{{ number_format($r->purchase_rate, 2) }}</td>
                        <td><strong>{{ number_format($r->total_amount, 2) }}</strong></td>
                        <td>{{ $r->received_datetime ? \Carbon\Carbon::parse($r->received_datetime)->format('d M Y H:i') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No refills in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $refills->links() }}
</div>
@endsection
