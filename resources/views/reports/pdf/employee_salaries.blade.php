@extends('reports.pdf.layout')

@section('title')
Employee Salaries Report
@endsection

@section('report-meta')
<strong>Range:</strong> {{ report_date($from) }} &mdash; {{ report_date($to) }}
· <strong>Total:</strong> {{ money($totalAmount) }}
@endsection

@section('content')

<style>
    table td.num, table th.num { text-align: right; }
    h3 { margin: 12px 0 8px; font-size: 13px; }
</style>

<div style="margin-bottom:10px;font-size:11px;">
    <strong>Full:</strong> {{ money($byType['full'] ?? 0) }}
    · <strong>Advance:</strong> {{ money($byType['advance'] ?? 0) }}
    · <strong>Partial:</strong> {{ money($byType['partial'] ?? 0) }}
    · <strong>Bonus:</strong> {{ money($byType['bonus'] ?? 0) }}
</div>

<h3>By Employee</h3>
@if($byEmployee->count())
<table>
    <thead>
        <tr>
            <th>Employee</th>
            <th class="num">Full</th>
            <th class="num">Advance</th>
            <th class="num">Partial</th>
            <th class="num">Bonus</th>
            <th class="num">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($byEmployee as $row)
            <tr>
                <td>{{ $row['name'] }}</td>
                <td class="num">{{ money($row['full']) }}</td>
                <td class="num">{{ money($row['advance']) }}</td>
                <td class="num">{{ money($row['partial']) }}</td>
                <td class="num">{{ money($row['bonus']) }}</td>
                <td class="num"><strong>{{ money($row['total']) }}</strong></td>
            </tr>
        @endforeach
    </tbody>
</table>
@else
<p>No salary payments in this period.</p>
@endif

<h3>Payment Details</h3>
@if($salaries->count())
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Employee</th>
            <th>Type</th>
            <th>Month</th>
            <th>Method</th>
            <th class="num">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach($salaries as $row)
            <tr>
                <td>{{ $row->payment_date->format('d M Y') }}</td>
                <td>{{ $row->employee->name ?? '—' }}</td>
                <td>{{ $row->typeLabel() }}</td>
                <td>{{ $row->salary_month->format('M Y') }}</td>
                <td>{{ ucfirst($row->payment_method) }}</td>
                <td class="num">{{ money($row->amount) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif

@endsection
