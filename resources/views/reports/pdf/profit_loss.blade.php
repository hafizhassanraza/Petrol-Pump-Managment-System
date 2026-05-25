@extends('reports.pdf.layout')

@section('title')
Profit &amp; Loss Report
@endsection

@section('content')

<div class="range-info">
    <strong>Range:</strong> {{ \Carbon\Carbon::parse($from)->format('d M Y') }} &mdash; {{ \Carbon\Carbon::parse($to)->format('d M Y') }}<br>
    <strong>Filter:</strong> {{ ucfirst(str_replace('-', ' ', $filter)) }}<br>
    <strong>Profit Margin:</strong> {{ $profitMargin }}%
</div>

<table>
    <thead>
        <tr>
            <th>Item</th>
            <th>Details</th>
            <th>Amount (PKR)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Total Sales</td>
            <td>{{ $salesCount }} shifts, {{ number_format($salesLiters, 2) }} L</td>
            <td>{{ number_format($sales, 2) }}</td>
        </tr>
        <tr>
            <td>Operating Expenses</td>
            <td>{{ $expenseCount }} entries ({{ $expenseRatio }}% of sales)</td>
            <td>- {{ number_format($expenses, 2) }}</td>
        </tr>
        <tr>
            <td>Owner Fuel Usage</td>
            <td>{{ $ownerFuelCount }} entries, {{ number_format($ownerFuelLiters, 2) }} L ({{ $ownerFuelRatio }}%)</td>
            <td>- {{ number_format($ownerFuel, 2) }}</td>
        </tr>
        <tr>
            <td>Tank Refill COGS</td>
            <td>{{ number_format($refillLiters, 2) }} L purchased</td>
            <td>- {{ number_format($refillCogs, 2) }}</td>
        </tr>
        <tr>
            <td>Gross Profit</td>
            <td>Before refill COGS</td>
            <td>{{ number_format($grossProfit, 2) }}</td>
        </tr>
        <tr>
            <td>Total Costs (incl. COGS)</td>
            <td></td>
            <td>- {{ number_format($totalCosts, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Net Profit / Loss</strong></td>
            <td></td>
            <td><strong>{{ number_format($netProfit, 2) }}</strong></td>
        </tr>
    </tbody>
</table>

@if($expenseByType->count())
    <h3 style="margin-top: 18px; margin-bottom: 8px;">Expense Breakdown by Type</h3>
    <table>
        <thead>
            <tr>
                <th>Type</th>
                <th>Amount (PKR)</th>
                <th>Entries</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expenseByType as $row)
                <tr>
                    <td>{{ $row->expense_type }}</td>
                    <td>{{ number_format($row->total, 2) }}</td>
                    <td>{{ $row->count }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

@if($dailyBreakdown->count())
    <h3 style="margin-top: 18px; margin-bottom: 8px;">Daily Breakdown</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Sales</th>
                <th>Expenses</th>
                <th>Owner Fuel</th>
                <th>Costs</th>
                <th>Net</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dailyBreakdown as $day)
                <tr>
                    <td>{{ $day['label'] }}</td>
                    <td>{{ number_format($day['sales'], 2) }}</td>
                    <td>{{ number_format($day['expenses'], 2) }}</td>
                    <td>{{ number_format($day['owner_fuel'], 2) }}</td>
                    <td>{{ number_format($day['costs'], 2) }}</td>
                    <td>{{ number_format($day['net'], 2) }}</td>
                </tr>
            @endforeach
            <tr>
                <td><strong>Period Total</strong></td>
                <td><strong>{{ number_format($sales, 2) }}</strong></td>
                <td><strong>{{ number_format($expenses, 2) }}</strong></td>
                <td><strong>{{ number_format($ownerFuel, 2) }}</strong></td>
                <td><strong>{{ number_format($totalCosts, 2) }}</strong></td>
                <td><strong>{{ number_format($netProfit, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>
@endif

@endsection
