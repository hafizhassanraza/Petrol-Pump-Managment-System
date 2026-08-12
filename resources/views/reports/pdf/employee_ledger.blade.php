@extends('reports.pdf.layout')

@section('title')
Employee Payment Ledger
@endsection

@section('report-meta')
<strong>{{ $employee->name }}</strong> ({{ $employee->employee_code }})
· <strong>Range:</strong> {{ report_date($from) }} &mdash; {{ report_date($to) }}
· <strong>Total Paid:</strong> {{ money($totalPaid) }}
@endsection

@section('content')

<style>
    table td.num, table th.num { text-align: right; }
    .meta { margin-bottom: 10px; font-size: 11px; }
</style>

<div class="meta">
    <strong>Base salary:</strong> {{ money($employee->salary) }}
    @if($employee->phone) · <strong>Phone:</strong> {{ $employee->phone }} @endif
    · <strong>Full:</strong> {{ money($byType['full'] ?? 0) }}
    · <strong>Advance:</strong> {{ money($byType['advance'] ?? 0) }}
    · <strong>Partial:</strong> {{ money($byType['partial'] ?? 0) }}
    · <strong>Bonus:</strong> {{ money($byType['bonus'] ?? 0) }}
</div>

@if($ledgerRows->count())
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Date</th>
            <th>Type</th>
            <th>Month</th>
            <th>Method</th>
            <th>Ref</th>
            <th class="num">Amount</th>
            <th class="num">Running</th>
            <th>Notes</th>
        </tr>
    </thead>
    <tbody>
        @foreach($ledgerRows as $i => $row)
            @php $payment = $row['payment']; @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $payment->payment_date->format('d M Y') }}</td>
                <td>{{ $payment->typeLabel() }}</td>
                <td>{{ $payment->salary_month->format('M Y') }}</td>
                <td>{{ ucfirst($payment->payment_method) }}</td>
                <td>{{ $payment->reference_no ?: '—' }}</td>
                <td class="num">{{ money($payment->amount) }}</td>
                <td class="num">{{ money($row['balance']) }}</td>
                <td>{{ $payment->notes ?: '—' }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="6"><strong>Period Total</strong></td>
            <td class="num"><strong>{{ money($totalPaid) }}</strong></td>
            <td class="num"><strong>{{ money($totalPaid) }}</strong></td>
            <td></td>
        </tr>
    </tbody>
</table>
@else
<p>No salary payments in this period.</p>
@endif

@endsection
