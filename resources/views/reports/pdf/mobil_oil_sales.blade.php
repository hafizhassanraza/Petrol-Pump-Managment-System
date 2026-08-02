@extends('reports.pdf.layout')

@section('title')
Mobil Oil Sales Report
@endsection

@section('content')

<div class="range-info">
    <strong>Range:</strong> {{ \Carbon\Carbon::parse($from)->format('d M Y') }} &mdash; {{ \Carbon\Carbon::parse($to)->format('d M Y') }}<br>
    <strong>Total Sales:</strong> PKR {{ money($totalAmount) }}<br>
    <strong>Total Quantity:</strong> {{ number_format($totalQty, 2) }}
</div>

<table>
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
        @foreach($sales as $s)
            <tr>
                <td>{{ $s->product->name ?? '—' }}</td>
                <td>{{ number_format($s->quantity, 2) }}</td>
                <td>{{ rate($s->unit_price) }}</td>
                <td>{{ money($s->total_amount) }}</td>
                <td>{{ ucfirst($s->payment_method) }}</td>
                <td>{{ $s->employee->name ?? '—' }}</td>
                <td>{{ $s->sold_datetime?->format('d M Y H:i') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

@endsection
