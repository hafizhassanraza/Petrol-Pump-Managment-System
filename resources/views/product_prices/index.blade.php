@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <div class="row g-3">
        @foreach($products as $p)
            <div class="col-md-4">
                <div class="p-3 border rounded-3 bg-light">
                    <strong>{{ $p->name }}</strong><br>
                    @if($p->latestPrice)
                        <span class="fs-5 text-success fw-bold">PKR {{ number_format($p->latestPrice->price, 2) }}</span>
                        <small class="text-muted d-block">Since {{ $p->latestPrice->effective_from->format('d M Y H:i') }}</small>
                    @else
                        <span class="text-danger">No price set</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="page-card">
    <div class="list-toolbar">
        <a href="{{ route('product-prices.create') }}" class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Change Price</a>
    </div>
    <div class="table-container">
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price (PKR/L)</th>
                    <th>Effective From</th>
                    <th>Recorded</th>
                </tr>
            </thead>
            <tbody>
                @forelse($prices as $row)
                    <tr>
                        <td><strong>{{ $row->product->name ?? 'N/A' }}</strong></td>
                        <td>{{ number_format($row->price, 2) }}</td>
                        <td>{{ $row->effective_from->format('d M Y, h:i A') }}</td>
                        <td>{{ $row->created_at->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No price history.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $prices->links() }}
</div>
@endsection
