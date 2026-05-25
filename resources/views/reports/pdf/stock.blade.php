@extends('reports.pdf.layout')

@section('title')
Tank Stock Report
@endsection

@section('content')

<div class="range-info">
    <strong>Generated:</strong> {{ $generatedAt }}<br>
    <strong>Tanks:</strong> {{ $tankCount }} &nbsp;|&nbsp;
    <strong>Total Stock:</strong> {{ number_format($totalStock, 2) }} L &nbsp;|&nbsp;
    <strong>Total Capacity:</strong> {{ number_format($totalCapacity, 2) }} L &nbsp;|&nbsp;
    <strong>Low Stock Alerts:</strong> {{ $lowStockCount }}
</div>

<table>
    <thead>
        <tr>
            <th>Tank</th>
            <th>Product</th>
            <th>Capacity (L)</th>
            <th>Current (L)</th>
            <th>Available (L)</th>
            <th>Fill %</th>
            <th>Min Level</th>
            <th>Alert</th>
        </tr>
    </thead>
    <tbody>
        @foreach($tanks as $t)
            <tr>
                <td>{{ $t['tank_number'] }}</td>
                <td>{{ $t['product'] }}</td>
                <td>{{ number_format($t['capacity'], 2) }}</td>
                <td>{{ number_format($t['current_stock'], 2) }}</td>
                <td>{{ number_format($t['available'], 2) }}</td>
                <td>{{ $t['fill_percent'] }}%</td>
                <td>{{ number_format($t['minimum_level'], 2) }}</td>
                <td>{{ $t['is_low'] ? 'Low Stock' : 'OK' }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="2"><strong>Totals</strong></td>
            <td><strong>{{ number_format($totalCapacity, 2) }}</strong></td>
            <td><strong>{{ number_format($totalStock, 2) }}</strong></td>
            <td colspan="4"></td>
        </tr>
    </tbody>
</table>

@endsection
