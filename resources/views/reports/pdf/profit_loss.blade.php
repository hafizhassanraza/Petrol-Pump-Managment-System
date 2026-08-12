@extends('reports.pdf.layout')

@section('title')
Profit &amp; Loss Report
@endsection

@section('report-meta')
<strong>Range:</strong> {{ \Carbon\Carbon::parse($from)->format('d M Y') }} &mdash; {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
@endsection

@section('content')

<style>
    h3 { margin-top: 18px; margin-bottom: 8px; }
</style>

@include('reports.pdf.partials.product_breakdown', ['fuelBreakdownSimple' => true])
@include('reports.pdf.partials.mobil_oil_breakdown')

<h3>Total Sales &amp; Profit</h3>
<table>
    <thead>
        <tr>
            <th>Category</th>
            <th>Sales (PKR)</th>
            <th>Profit/Loss (PKR)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Petroleum</td>
            <td>{{ money($fuelSales) }}</td>
            <td>{{ money($fuelSalesProfit) }}</td>
        </tr>
        <tr>
            <td>Mobil Oil</td>
            <td>{{ money($mobilOilSales) }}</td>
            <td>{{ money($mobilOilSalesProfit) }}</td>
        </tr>
        <tr>
            <td><strong>Total</strong></td>
            <td><strong>{{ money($sales) }}</strong></td>
            <td><strong>{{ money($totalSalesProfit) }}</strong></td>
        </tr>
        <tr>
            <td>Operating Expenses</td>
            <td>—</td>
            <td>- {{ money($expenses) }}</td>
        </tr>
        <tr>
            <td>Employee Salaries</td>
            <td>—</td>
            <td>- {{ money($salaries) }}</td>
        </tr>
        <tr>
            <td>Owner Fuel Usage (excluded from sales)</td>
            <td>—</td>
            <td>{{ money($ownerFuel) }}</td>
        </tr>
        <tr>
            <td><strong>Total Operating Expense</strong></td>
            <td>—</td>
            <td><strong>- {{ money($operatingAndOwnerTotal) }}</strong></td>
        </tr>
        <tr>
            <td><strong>Net Profit (Inc. Total Expense)</strong></td>
            <td>—</td>
            <td><strong>{{ money($netSalesProfit) }}</strong></td>
        </tr>
    </tbody>
</table>

@endsection
