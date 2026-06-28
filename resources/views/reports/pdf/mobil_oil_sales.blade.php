@extends('reports.pdf.layout')

@section('title')
Mobil Oil Sales Report
@endsection

@section('content')

<div class="range-info">
    <strong>Range:</strong> {{ \Carbon\Carbon::parse($from)->format('d M Y') }} &mdash; {{ \Carbon\Carbon::parse($to)->format('d M Y') }}<br>
    <strong>Total Sales:</strong> PKR {{ number_format($totalAmount, 2) }}<br>
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
                <td>{{ number_format($s->unit_price, 2) }}</td>
                <td>{{ number_format($s->total_amount, 2) }}</td>
                <td>{{ ucfirst($s->payment_method) }}</td>
                <td>{{ $s->employee->name ?? '—' }}</td>
                <td>{{ $s->sold_datetime?->format('d M Y H:i') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

@endsection
