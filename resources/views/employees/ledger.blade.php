@extends('layouts.app')

@section('content')
@include('reports.partials.report-styles')

<div class="page-card mb-3">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h3 class="page-title mb-1">{{ $employee->name }}</h3>
            <p class="page-subtitle mb-0">
                {{ $employee->employee_code }}
                @if($employee->phone) · {{ $employee->phone }} @endif
                · Base salary PKR {{ money($employee->salary) }}
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('employee-salaries.create', ['employee_id' => $employee->id]) }}" class="btn btn-success btn-sm">
                <i class="bi bi-plus-lg"></i> Add Payment
            </a>
            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-warning btn-sm">Edit</a>
            <a href="{{ route('employees.index') }}" class="btn btn-secondary btn-sm">Back</a>
        </div>
    </div>
</div>

@include('partials.period-filter', ['formAction' => route('employees.ledger', $employee)])

<div class="row mb-3">
    <div class="col-md-3 mb-2">
        <div class="info-card amount">
            <div class="info-card-label">Total Paid</div>
            <div class="info-card-value">PKR {{ money($totalPaid) }}</div>
        </div>
    </div>
    @foreach(['full' => 'success', 'advance' => 'warning', 'partial' => 'secondary', 'bonus' => 'records'] as $type => $style)
        <div class="col-md-2 mb-2">
            <div class="info-card {{ $style }}">
                <div class="info-card-label">{{ $typeLabels[$type] }}</div>
                <div class="info-card-value" style="font-size:1.2rem;">PKR {{ money($byType[$type] ?? 0) }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="download-section mb-3">
    <a href="{{ route('employees.ledger.pdf', array_merge(['employee' => $employee], request()->query())) }}" class="btn-download btn-download-pdf">
        <i class="bi bi-file-pdf"></i> Download PDF
    </a>
</div>

<div class="table-container">
    <h5 class="p-3 mb-0">Payment Ledger</h5>
    <table class="excel-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Type</th>
                <th>Salary Month</th>
                <th>Method</th>
                <th>Reference</th>
                <th style="text-align:right;">Amount (PKR)</th>
                <th style="text-align:right;">Running Total</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ledgerRows as $i => $row)
                @php $payment = $row['payment']; @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $payment->payment_date->format('d M Y') }}</td>
                    <td>
                        <span class="badge
                            @if($payment->type === 'full') bg-success
                            @elseif($payment->type === 'advance') bg-warning text-dark
                            @elseif($payment->type === 'partial') bg-primary
                            @else bg-info text-dark
                            @endif">
                            {{ $payment->typeLabel() }}
                        </span>
                    </td>
                    <td>{{ $payment->salary_month->format('M Y') }}</td>
                    <td>{{ ucfirst($payment->payment_method) }}</td>
                    <td>{{ $payment->reference_no ?: '—' }}</td>
                    <td style="text-align:right;font-weight:600;">{{ money($payment->amount) }}</td>
                    <td style="text-align:right;">{{ money($row['balance']) }}</td>
                    <td>{{ $payment->notes ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">No salary payments in this period.</td>
                </tr>
            @endforelse
        </tbody>
        @if($ledgerRows->count())
            <tfoot>
                <tr>
                    <td colspan="6" class="fw-bold">Period Total</td>
                    <td style="text-align:right;" class="fw-bold">{{ money($totalPaid) }}</td>
                    <td style="text-align:right;" class="fw-bold">{{ money($totalPaid) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
@endsection
