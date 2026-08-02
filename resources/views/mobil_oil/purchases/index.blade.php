@extends('layouts.app')

@section('content')
@include('partials.period-filter')

<div class="page-card">
    <div class="list-toolbar">
        <a href="{{ route('mobil-oil.purchases.create') }}" class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Add Purchase</a>
    </div>
    <div class="table-container">
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Invoice</th>
                    <th>Qty</th>
                    <th>Rate</th>
                    <th>Total</th>
                    <th>Received</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $p)
                    <tr>
                        <td>{{ $p->product->name ?? '—' }}</td>
                        <td>{{ $p->invoice_no ?? '—' }}</td>
                        <td>{{ number_format($p->quantity, 2) }} {{ $p->product->unit ?? '' }}</td>
                        <td>{{ number_format($p->purchase_rate, 2) }}</td>
                        <td><strong>{{ money($p->total_amount) }}</strong></td>
                        <td>{{ $p->received_datetime?->format('d M Y H:i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No purchases in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $purchases->links() }}
</div>
@endsection
