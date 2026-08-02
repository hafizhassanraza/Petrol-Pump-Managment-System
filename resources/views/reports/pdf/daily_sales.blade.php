@extends('reports.pdf.layout')

@section('title')
Daily Sales Report
@endsection

@section('report-meta')
<strong>Range:</strong> {{ report_date($from) }} &mdash; {{ report_date($to) }}
@endsection

@section('content')

<style>
    h3 { margin-top: 18px; margin-bottom: 8px; }
    .stack-cell { text-align: right; line-height: 1.35; font-size: 11px; }
    .stack-cell .amt { font-size: 12px; font-weight: bold; }
    .stack-cell .sub { color: #334155; }
    .stack-cell .block-label { color: #64748b; font-size: 10px; }
    .stack-cell .block-value { font-weight: bold; margin-bottom: 4px; }
    .info-row td { background: #f1f5f9; font-size: 10px; text-align: left; }
</style>

@include('reports.pdf.partials.product_breakdown', ['fuelBreakdownSimple' => true])
@include('reports.pdf.partials.mobil_oil_breakdown')

@if(isset($dailyTotals) && $dailyTotals->count())
    <h3>Daily Breakdown</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Petrol</th>
                <th>Diesel</th>
                <th>Mobil Oil</th>
                <th>Total Amount</th>
                <th>Profit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dailyTotals as $day)
                <tr>
                    <td>{{ $day['label'] }}</td>
                    <td>@include('reports.pdf.partials.daily_stack_cell', ['row' => $day['petrol']])</td>
                    <td>@include('reports.pdf.partials.daily_stack_cell', ['row' => $day['diesel']])</td>
                    <td>@include('reports.pdf.partials.daily_stack_cell', ['row' => $day['mobil_oil']])</td>
                    <td>
                        <div class="stack-cell">
                            <div class="amt">{{ money($day['total_amount']) }}</div>
                            <div class="sub">Cash {{ money($day['total_cash']) }}</div>
                            <div class="sub">Bank {{ money($day['total_online']) }}</div>
                        </div>
                    </td>
                    <td><strong>{{ money($day['total_profit']) }}</strong></td>
                </tr>
                @foreach(($day['infos'] ?? []) as $info)
                    <tr class="info-row">
                        <td colspan="6">{{ $info['message'] }}</td>
                    </tr>
                @endforeach
            @endforeach
            @php
                $lastDay = $dailyTotals->last();
            @endphp
            <tr>
                <td><strong>Grand Total</strong></td>
                <td>
                    <div class="stack-cell">
                        <div>Close {{ number_format($lastDay['petrol']['stock_closing'] ?? 0, 2) }} L</div>
                        <div class="amt">{{ money($dailyTotals->sum(fn ($d) => $d['petrol']['sales_amount'] ?? 0)) }}</div>
                        <div class="sub">Profit {{ money($dailyTotals->sum(fn ($d) => $d['petrol']['total_profit'] ?? 0)) }}</div>
                    </div>
                </td>
                <td>
                    <div class="stack-cell">
                        <div>Close {{ number_format($lastDay['diesel']['stock_closing'] ?? 0, 2) }} L</div>
                        <div class="amt">{{ money($dailyTotals->sum(fn ($d) => $d['diesel']['sales_amount'] ?? 0)) }}</div>
                        <div class="sub">Profit {{ money($dailyTotals->sum(fn ($d) => $d['diesel']['total_profit'] ?? 0)) }}</div>
                    </div>
                </td>
                <td>
                    <div class="stack-cell">
                        <div class="amt">{{ money($dailyTotals->sum(fn ($d) => $d['mobil_oil']['sales_amount'] ?? 0)) }}</div>
                        <div class="sub">Profit {{ money($dailyTotals->sum(fn ($d) => $d['mobil_oil']['total_profit'] ?? 0)) }}</div>
                    </div>
                </td>
                <td>
                    <div class="stack-cell">
                        <div class="amt">{{ money($dailyTotals->sum('total_amount')) }}</div>
                        <div class="sub">Cash {{ money($dailyTotals->sum('total_cash')) }}</div>
                        <div class="sub">Bank {{ money($dailyTotals->sum('total_online')) }}</div>
                    </div>
                </td>
                <td><strong>{{ money($dailyTotals->sum('total_profit')) }}</strong></td>
            </tr>
        </tbody>
    </table>
@endif

@endsection
