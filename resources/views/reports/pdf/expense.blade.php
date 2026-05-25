@extends('reports.pdf.layout')

@section('title')
Expense Report
@endsection

@section('content')

<div class="range-info">
    <strong>Range:</strong> {{ \Carbon\Carbon::parse($from)->format('d M Y') }} &mdash; {{ \Carbon\Carbon::parse($to)->format('d M Y') }}<br>
    <strong>Filter:</strong> {{ ucfirst(str_replace('-', ' ', $filter)) }}<br>
    <strong>Total Amount:</strong> PKR {{ number_format($totalAmount, 2) }} &nbsp;|&nbsp;
    <strong>Records:</strong> {{ $totalRecords }}
</div>

<table>
    <thead>
        <tr>
            <th>Type</th>
            <th>Amount (PKR)</th>
            <th>Date</th>
            <th>Notes</th>
        </tr>
    </thead>
    <tbody>
        @foreach($expenses as $e)
            <tr>
                <td>{{ $e->expense_type }}</td>
                <td>{{ number_format($e->amount, 2) }}</td>
                <td>{{ \Carbon\Carbon::parse($e->expense_date)->format('d-m-Y') }}</td>
                <td>{{ $e->notes ?? '' }}</td>
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
                <th>Amount (PKR)</th>
                <th>Records</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dailyTotals as $day)
                <tr>
                    <td>{{ $day['label'] }}</td>
                    <td>{{ number_format($day['total_amount'], 2) }}</td>
                    <td>{{ $day['record_count'] }}</td>
                </tr>
            @endforeach
            <tr>
                <td><strong>Grand Total</strong></td>
                <td><strong>{{ number_format($totalAmount, 2) }}</strong></td>
                <td><strong>{{ $totalRecords }}</strong></td>
            </tr>
        </tbody>
    </table>
@endif

@endsection
