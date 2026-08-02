@extends('reports.pdf.layout')

@section('title')
Cash Report
@endsection

@section('report-meta')
<strong>Range:</strong> {{ report_date($from) }} &mdash; {{ report_date($to) }}
@endsection

@section('content')

<style>
    h3 { margin-top: 12px; margin-bottom: 8px; }
    .summary { margin-bottom: 12px; font-size: 11px; }
    .summary span { margin-right: 14px; }
    table td.num, table th.num { text-align: right; }
</style>

<div class="summary">
    <span><strong>Sales Cash:</strong> {{ money($total_sales_cash) }}</span>
    <span><strong>Cash In:</strong> {{ money($total_cash_in) }}</span>
    <span><strong>Cash Out:</strong> {{ money($total_cash_out) }}</span>
    <span><strong>Expenses:</strong> {{ money($total_expenses) }}</span>
    <span><strong>Closing:</strong> {{ money($closing_balance) }}</span>
</div>

<h3>Daily Cash Ledger</h3>
@if($days->count())
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th class="num">Sales Cash</th>
                <th class="num">Sales Bank</th>
                <th class="num">Cash In</th>
                <th class="num">Cash Out</th>
                <th class="num">Expenses</th>
                <th class="num">Closing</th>
            </tr>
        </thead>
        <tbody>
            @foreach($days as $day)
                <tr>
                    <td>{{ $day['label'] }}</td>
                    <td class="num">{{ money($day['sales_cash']) }}</td>
                    <td class="num">{{ money($day['sales_bank']) }}</td>
                    <td class="num">{{ money($day['cash_in']) }}</td>
                    <td class="num">{{ money($day['cash_out']) }}</td>
                    <td class="num">{{ money($day['expenses']) }}</td>
                    <td class="num"><strong>{{ money($day['closing']) }}</strong></td>
                </tr>
            @endforeach
            <tr>
                <td><strong>Period Total</strong></td>
                <td class="num"><strong>{{ money($total_sales_cash) }}</strong></td>
                <td class="num"><strong>{{ money($total_sales_bank) }}</strong></td>
                <td class="num"><strong>{{ money($total_cash_in) }}</strong></td>
                <td class="num"><strong>{{ money($total_cash_out) }}</strong></td>
                <td class="num"><strong>{{ money($total_expenses) }}</strong></td>
                <td class="num"><strong>{{ money($closing_balance) }}</strong></td>
            </tr>
        </tbody>
    </table>
@else
    <p>No cash activity found for the selected date range.</p>
@endif

@endsection
