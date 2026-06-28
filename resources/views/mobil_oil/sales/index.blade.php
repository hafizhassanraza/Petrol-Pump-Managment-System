@extends('layouts.app')

@section('content')
@include('partials.period-filter')

<div class="page-card mb-3">
    <div class="row g-3">
        <div class="col-md-4">
            <div class="p-3 border rounded-3 bg-light">
                <small class="text-muted">Period Total Sales</small>
                <div class="fs-4 fw-bold text-success">PKR {{ number_format($totalAmount, 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="page-card">
    <div class="list-toolbar">
        <a href="{{ route('mobil-oil.sales.create') }}" class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Record Sale</a>
    </div>
    <div class="table-container">
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Employee</th>
                    <th>Sold At</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $s)
                    <tr>
                        <td>{{ $s->product->name ?? '—' }}</td>
                        <td>{{ number_format($s->quantity, 2) }} {{ $s->product->unit ?? '' }}</td>
                        <td>{{ number_format($s->unit_price, 2) }}</td>
                        <td><strong>{{ number_format($s->total_amount, 2) }}</strong></td>
                        <td>{{ ucfirst($s->payment_method) }}</td>
                        <td>{{ $s->employee->name ?? '—' }}</td>
                        <td>{{ $s->sold_datetime?->format('d M Y H:i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No sales in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $sales->links() }}
</div>
@endsection
