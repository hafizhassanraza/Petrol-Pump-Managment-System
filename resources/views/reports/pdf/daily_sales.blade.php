@extends('reports.pdf.layout')

@section('title')
Daily Sales Report
@endsection

@section('content')

<div class="range-info">
    <strong>Range:</strong> {{ \Carbon\Carbon::parse($from)->format('d M Y') }} &mdash; {{ \Carbon\Carbon::parse($to)->format('d M Y') }}<br>
    <strong>Filter:</strong> {{ ucfirst(str_replace('-', ' ', $filter)) }}
</div>

<table>
    <thead>
        <tr>
            <th>Employee</th>
            <th>Nozzle</th>
            <th>Liters</th>
            <th>Amount</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach($shifts as $s)
            <tr>
                <td>{{ $s->employee->name ?? '' }}</td>
                <td>{{ $s->nozzle->nozzle_number ?? '' }}</td>
                <td>{{ number_format($s->total_liters, 2) }}</td>
                <td>{{ number_format($s->total_amount, 2) }}</td>
                <td>{{ $s->created_at->format('d-m-Y H:i') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

@if(isset($dailyTotals) && $dailyTotals->count())
    <h3 style="margin-top: 18px; margin-bottom: 8px;">Daily Totals</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Liters</th>
                <th>Amount</th>
                <th>Records</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dailyTotals as $day)
                <tr>
                    <td>{{ $day['label'] }}</td>
                    <td>{{ number_format($day['total_liters'], 2) }}</td>
                    <td>{{ number_format($day['total_amount'], 2) }}</td>
                    <td>{{ $day['record_count'] }}</td>
                </tr>
            @endforeach
            <tr>
                <td><strong>Grand Total</strong></td>
                <td><strong>{{ number_format($totalLiters, 2) }}</strong></td>
                <td><strong>{{ number_format($totalAmount, 2) }}</strong></td>
                <td><strong>{{ $shifts->count() }}</strong></td>
            </tr>
        </tbody>
    </table>
@endif

@endsection