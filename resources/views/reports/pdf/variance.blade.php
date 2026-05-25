@extends('reports.pdf.layout')

@section('title')
Tank Variance Report
@endsection

@section('content')

<div class="range-info">
    <strong>Generated:</strong> {{ $generatedAt }}<br>
    <strong>Tanks:</strong> {{ $tankCount }} &nbsp;|&nbsp;
    <strong>Matched:</strong> {{ $matchedCount }} &nbsp;|&nbsp;
    <strong>With Variance:</strong> {{ $tanksWithVariance }} &nbsp;|&nbsp;
    <strong>Total Variance:</strong> {{ number_format($totalVariance, 2) }} L
</div>

<table>
    <thead>
        <tr>
            <th>Tank</th>
            <th>Product</th>
            <th>System (L)</th>
            <th>Physical (L)</th>
            <th>Difference (L)</th>
            <th>Status</th>
            <th>Last Dip</th>
        </tr>
    </thead>
    <tbody>
        @foreach($variances as $v)
            <tr>
                <td>{{ $v['tank_number'] }}</td>
                <td>{{ $v['product'] }}</td>
                <td>{{ number_format($v['system'], 2) }}</td>
                <td>{{ number_format($v['physical'], 2) }}</td>
                <td>{{ number_format($v['difference'], 2) }}</td>
                <td>{{ $v['status_label'] }}</td>
                <td>{{ $v['dip_date'] ?? 'No dip reading' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

@endsection
