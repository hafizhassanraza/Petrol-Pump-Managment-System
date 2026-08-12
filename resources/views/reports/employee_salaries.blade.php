@extends('layouts.app')

@section('content')
@include('reports.partials.report-styles')
@include('partials.period-filter', ['formAction' => route('reports.employee-salaries')])

<div class="row mb-3">
    <div class="col-md-3 mb-2">
        <div class="info-card amount">
            <div class="info-card-label">Total Salaries</div>
            <div class="info-card-value">PKR {{ money($totalAmount) }}</div>
        </div>
    </div>
    @foreach(['full' => 'success', 'advance' => 'warning', 'partial' => 'secondary', 'bonus' => 'amount'] as $type => $style)
        <div class="col-md-2 mb-2">
            <div class="info-card {{ $style }}">
                <div class="info-card-label">{{ $typeLabels[$type] }}</div>
                <div class="info-card-value" style="font-size:1.2rem;">PKR {{ money($byType[$type] ?? 0) }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="download-section mb-3">
    <a href="{{ route('reports.employee-salaries.pdf', request()->query()) }}" class="btn-download btn-download-pdf">
        <i class="bi bi-file-pdf"></i> Download PDF
    </a>
</div>

<div class="table-container mb-4">
    <h5 class="p-3 mb-0">By Employee</h5>
    <table class="excel-table">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Base Salary</th>
                <th style="text-align:right;">Full</th>
                <th style="text-align:right;">Advance</th>
                <th style="text-align:right;">Partial</th>
                <th style="text-align:right;">Bonus</th>
                <th style="text-align:right;">Total Paid</th>
                <th>Records</th>
            </tr>
        </thead>
        <tbody>
            @forelse($byEmployee as $row)
                <tr>
                    <td>
                        <strong>{{ $row['name'] }}</strong>
                        <div class="small text-muted">{{ $row['code'] }}</div>
                    </td>
                    <td>{{ money($row['base_salary']) }}</td>
                    <td style="text-align:right;">{{ money($row['full']) }}</td>
                    <td style="text-align:right;">{{ money($row['advance']) }}</td>
                    <td style="text-align:right;">{{ money($row['partial']) }}</td>
                    <td style="text-align:right;">{{ money($row['bonus']) }}</td>
                    <td style="text-align:right;font-weight:700;">{{ money($row['total']) }}</td>
                    <td>{{ $row['count'] }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No salary payments in this period.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="table-container">
    <h5 class="p-3 mb-0">Payment Details</h5>
    <table class="excel-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Employee</th>
                <th>Type</th>
                <th>Month</th>
                <th>Method</th>
                <th style="text-align:right;">Amount</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($salaries as $row)
                <tr>
                    <td>{{ $row->payment_date->format('d M Y') }}</td>
                    <td>{{ $row->employee->name ?? '—' }}</td>
                    <td>{{ $row->typeLabel() }}</td>
                    <td>{{ $row->salary_month->format('M Y') }}</td>
                    <td>{{ ucfirst($row->payment_method) }}</td>
                    <td style="text-align:right;">{{ money($row->amount) }}</td>
                    <td>{{ $row->notes ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No records.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
