@extends('layouts.app')

@section('content')
@include('partials.period-filter')

<div class="row mb-3">
    <div class="col-md-4 col-lg mb-2">
        <div class="page-card py-3 px-3">
            <small class="text-muted text-uppercase">Total Paid</small>
            <div class="fs-4 fw-bold">PKR {{ money($totals['all']) }}</div>
        </div>
    </div>
    <div class="col-md-4 col-lg mb-2">
        <div class="page-card py-3 px-3">
            <small class="text-muted text-uppercase">Full Salary</small>
            <div class="fs-5 fw-bold text-success">PKR {{ money($totals['full']) }}</div>
        </div>
    </div>
    <div class="col-md-4 col-lg mb-2">
        <div class="page-card py-3 px-3">
            <small class="text-muted text-uppercase">Advance</small>
            <div class="fs-5 fw-bold text-warning">PKR {{ money($totals['advance']) }}</div>
        </div>
    </div>
    <div class="col-md-4 col-lg mb-2">
        <div class="page-card py-3 px-3">
            <small class="text-muted text-uppercase">Partial</small>
            <div class="fs-5 fw-bold text-primary">PKR {{ money($totals['partial']) }}</div>
        </div>
    </div>
    <div class="col-md-4 col-lg mb-2">
        <div class="page-card py-3 px-3">
            <small class="text-muted text-uppercase">Bonus</small>
            <div class="fs-5 fw-bold text-info">PKR {{ money($totals['bonus']) }}</div>
        </div>
    </div>
</div>

<div class="page-card">
    <div class="list-toolbar d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
        <form method="GET" action="{{ route('employee-salaries.index') }}" class="d-flex flex-wrap gap-2 align-items-end">
            <input type="hidden" name="filter" value="{{ $filter }}">
            <input type="hidden" name="from" value="{{ $from }}">
            <input type="hidden" name="to" value="{{ $to }}">

            <div>
                <label class="form-label small mb-1">Type</label>
                <select name="type" class="form-control form-control-sm">
                    <option value="">All types</option>
                    @foreach($typeLabels as $value => $label)
                        <option value="{{ $value }}" @selected($typeFilter === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label small mb-1">Employee</label>
                <select name="employee_id" class="form-control form-control-sm">
                    <option value="">All employees</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" @selected((int) $employeeFilter === (int) $employee->id)>
                            {{ $employee->name }} ({{ $employee->employee_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Apply</button>
        </form>

        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('reports.employee-salaries', request()->query()) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-bar-chart"></i> Report
            </a>
            <a href="{{ route('employee-salaries.create', ['type' => 'advance']) }}" class="btn btn-warning btn-sm">
                <i class="bi bi-lightning"></i> Advance
            </a>
            <a href="{{ route('employee-salaries.create', ['type' => 'partial']) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pie-chart"></i> Partial
            </a>
            <a href="{{ route('employee-salaries.create') }}" class="btn btn-success btn-sm">
                <i class="bi bi-plus-lg"></i> Add Salary
            </a>
        </div>
    </div>

    <div class="table-container">
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Employee</th>
                    <th>Type</th>
                    <th>Salary Month</th>
                    <th>Method</th>
                    <th style="text-align:right;">Amount (PKR)</th>
                    <th>Notes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($salaries as $row)
                    <tr>
                        <td>{{ $row->payment_date->format('d M Y') }}</td>
                        <td>
                            <strong>{{ $row->employee->name ?? '—' }}</strong>
                            <div class="small text-muted">
                                {{ $row->employee->employee_code ?? '' }}
                                @if($row->employee)
                                    · <a href="{{ route('employees.ledger', $row->employee) }}">Ledger</a>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="badge
                                @if($row->type === 'full') bg-success
                                @elseif($row->type === 'advance') bg-warning text-dark
                                @elseif($row->type === 'partial') bg-primary
                                @else bg-info text-dark
                                @endif">
                                {{ $row->typeLabel() }}
                            </span>
                        </td>
                        <td>{{ $row->salary_month->format('M Y') }}</td>
                        <td>{{ ucfirst($row->payment_method) }}</td>
                        <td style="text-align:right;font-weight:600;">{{ money($row->amount) }}</td>
                        <td>{{ $row->notes ?: ($row->reference_no ?: '—') }}</td>
                        <td class="text-nowrap">
                            <a href="{{ route('employee-salaries.edit', $row) }}" class="btn btn-primary btn-sm">Edit</a>
                            <form method="POST" action="{{ route('employee-salaries.destroy', $row) }}" class="d-inline"
                                  onsubmit="return confirm('Delete this salary record?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No salary records in this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $salaries->links() }}
</div>
@endsection
