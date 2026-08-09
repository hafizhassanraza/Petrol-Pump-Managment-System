@extends('reports.pdf.layout')

@section('title')
Shift Report
@endsection

@section('report-meta')
<strong>Range:</strong> {{ report_date($from) }} &mdash; {{ report_date($to) }}
@endsection

@section('content')

<style>
    table { font-size: 9px; }
    th, td { padding: 3px; }
    .stock-cell { text-align: right; line-height: 1.3; }
</style>

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Employee</th>
            <th>Nozzle</th>
            <th>Fuel</th>
            <th>Opening Meter</th>
            <th>Closing Meter</th>
            <th>Closing Stock</th>
            <th>Testing</th>
            <th>Liters</th>
            <th>Rate</th>
            <th>Amount</th>
            <th>Cash</th>
            <th>Bank</th>
            <th>Shortage</th>
            <th>Extra</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($shifts as $s)
            @php
                $isOpen = $s->status === 'active';
                $dateKey = \Carbon\Carbon::parse($s->closed_date ?? $s->assigned_date)->format('Y-m-d');
                $closing = $closingByDay->get($dateKey, [
                    'petrol' => ['stock_closing' => 0.0],
                    'diesel' => ['stock_closing' => 0.0],
                ]);
            @endphp
            <tr>
                <td>{{ report_date($s->closed_date ?? $s->assigned_date) }}</td>
                <td>{{ $s->employee->name ?? '' }}</td>
                <td>{{ $s->nozzle->nozzle_number ?? '' }}</td>
                <td>{{ $s->nozzle->product->name ?? '' }}</td>
                <td>{{ $s->opening_reading !== null ? number_format((float) $s->opening_reading, 2) : '' }}</td>
                <td>{{ $s->closing_reading !== null ? number_format((float) $s->closing_reading, 2) : '' }}</td>
                <td>
                    <div class="stock-cell">
                        <div>P {{ number_format($closing['petrol']['stock_closing'] ?? 0, 2) }}</div>
                        <div>D {{ number_format($closing['diesel']['stock_closing'] ?? 0, 2) }}</div>
                    </div>
                </td>
                <td>{{ $isOpen ? '' : number_format((float) ($s->testing_liters ?? 0), 2) }}</td>
                <td>{{ $isOpen ? '' : number_format((float) ($s->total_liters ?? 0), 2) }}</td>
                <td>{{ $isOpen || $s->price_per_liter === null ? '' : rate($s->price_per_liter) }}</td>
                <td>{{ $isOpen ? '' : money($s->total_amount) }}</td>
                <td>{{ $isOpen ? '' : money($s->cash_received) }}</td>
                <td>{{ $isOpen ? '' : money($s->online_received) }}</td>
                <td>{{ $isOpen ? '' : money($s->shortage_amount) }}</td>
                <td>{{ $isOpen ? '' : money($s->extra_amount) }}</td>
                <td>{{ ucfirst((string) $s->status) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="16">No shifts found</td>
            </tr>
        @endforelse
        @if($shifts->count())
            <tr>
                <td colspan="8"><strong>Grand Total</strong></td>
                <td><strong>{{ number_format($totalLiters, 2) }}</strong></td>
                <td></td>
                <td><strong>{{ money($totalAmount) }}</strong></td>
                <td><strong>{{ money($totalCash) }}</strong></td>
                <td><strong>{{ money($totalOnline) }}</strong></td>
                <td><strong>{{ money($totalShortage) }}</strong></td>
                <td><strong>{{ money($totalExtra) }}</strong></td>
                <td></td>
            </tr>
        @endif
    </tbody>
</table>

@endsection
