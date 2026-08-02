@extends('reports.pdf.layout')

@section('title')
Purchase Report
@endsection

@section('report-meta')
<strong>Range:</strong> {{ \Carbon\Carbon::parse($from)->format('d M Y') }} &mdash; {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
@endsection

@section('content')

<style>
    h3 { margin-top: 18px; margin-bottom: 8px; }
</style>

<table>
    <thead>
        <tr>
            <th>Petroleum Purchases</th>
            <th>Mobil Oil Purchases</th>
            <th>Total Purchases</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ money($fuelPurchaseAmount) }}</td>
            <td>{{ money($mobilOilPurchaseAmount) }}</td>
            <td><strong>{{ money($totalPurchaseAmount) }}</strong></td>
        </tr>
    </tbody>
</table>

<h3>Petroleum Purchases</h3>
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Product</th>
            <th>Tank</th>
            <th>Invoice</th>
            <th>Qty (L)</th>
            <th>Rate</th>
            <th>Amount (PKR)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($fuelPurchases as $p)
            <tr>
                <td>{{ optional($p->received_datetime)->format('d-m-Y H:i') }}</td>
                <td>{{ $p->product->name ?? '' }}</td>
                <td>{{ $p->tank->tank_number ?? '' }}</td>
                <td>{{ $p->invoice_no ?? '' }}</td>
                <td>{{ number_format((float) $p->quantity_liters, 2) }}</td>
                <td>{{ rate($p->purchase_rate) }}</td>
                <td>{{ money($p->total_amount) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7">No petroleum purchases</td>
            </tr>
        @endforelse
        @if($fuelPurchases->count())
            <tr>
                <td colspan="4"><strong>Total</strong></td>
                <td><strong>{{ number_format($fuelPurchaseLiters, 2) }}</strong></td>
                <td></td>
                <td><strong>{{ money($fuelPurchaseAmount) }}</strong></td>
            </tr>
        @endif
    </tbody>
</table>

<h3>Mobil Oil Purchases</h3>
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Product</th>
            <th>Invoice</th>
            <th>Qty</th>
            <th>Rate</th>
            <th>Amount (PKR)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($mobilOilPurchases as $p)
            <tr>
                <td>{{ optional($p->received_datetime)->format('d-m-Y H:i') }}</td>
                <td>{{ $p->product->name ?? '' }}{{ $p->product?->unit ? ' ('.$p->product->unit.')' : '' }}</td>
                <td>{{ $p->invoice_no ?? '' }}</td>
                <td>{{ number_format((float) $p->quantity, 2) }}</td>
                <td>{{ rate($p->purchase_rate) }}</td>
                <td>{{ money($p->total_amount) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6">No mobil oil purchases</td>
            </tr>
        @endforelse
        @if($mobilOilPurchases->count())
            <tr>
                <td colspan="3"><strong>Total</strong></td>
                <td><strong>{{ number_format($mobilOilPurchaseQty, 2) }}</strong></td>
                <td></td>
                <td><strong>{{ money($mobilOilPurchaseAmount) }}</strong></td>
            </tr>
        @endif
    </tbody>
</table>

@endsection
