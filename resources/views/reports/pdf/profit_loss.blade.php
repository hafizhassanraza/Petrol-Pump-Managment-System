@extends('reports.pdf.layout')

@section('title')
Profit & Loss Report
@endsection

@section('content')

<table>

<tr>
    <th>Total Sales</th>
    <td>{{ $sales }}</td>
</tr>

<tr>
    <th>Total Expenses</th>
    <td>{{ $expenses }}</td>
</tr>

<tr>
    <th>Owner Fuel Usage</th>
    <td>{{ $ownerFuel }}</td>
</tr>

<tr>
    <th>Net Profit</th>
    <td><b>{{ $netProfit }}</b></td>
</tr>

</table>

@endsection