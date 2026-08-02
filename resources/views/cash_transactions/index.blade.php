@extends('layouts.app')

@section('content')
@include('partials.period-filter')

<div class="row mb-3">
    <div class="col-md-4 mb-2">
        <div class="page-card py-3 px-3">
            <small class="text-muted text-uppercase">Cash In</small>
            <div class="fs-4 fw-bold text-success">PKR {{ money($totalIn) }}</div>
        </div>
    </div>
    <div class="col-md-4 mb-2">
        <div class="page-card py-3 px-3">
            <small class="text-muted text-uppercase">Cash Out</small>
            <div class="fs-4 fw-bold text-danger">PKR {{ money($totalOut) }}</div>
        </div>
    </div>
    <div class="col-md-4 mb-2">
        <div class="page-card py-3 px-3">
            <small class="text-muted text-uppercase">Net (In − Out)</small>
            <div class="fs-4 fw-bold {{ $netCash >= 0 ? 'text-success' : 'text-danger' }}">
                PKR {{ money($netCash) }}
            </div>
        </div>
    </div>
</div>

<div class="page-card">
    <div class="list-toolbar d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('cash-transactions.index', array_merge(request()->except('type'), ['type' => ''])) }}"
               class="btn btn-sm {{ empty($typeFilter) ? 'btn-dark' : 'btn-outline-secondary' }}">All</a>
            <a href="{{ route('cash-transactions.index', array_merge(request()->except('page'), ['type' => 'cash_in'])) }}"
               class="btn btn-sm {{ ($typeFilter ?? '') === 'cash_in' ? 'btn-success' : 'btn-outline-success' }}">Cash In</a>
            <a href="{{ route('cash-transactions.index', array_merge(request()->except('page'), ['type' => 'cash_out'])) }}"
               class="btn btn-sm {{ ($typeFilter ?? '') === 'cash_out' ? 'btn-danger' : 'btn-outline-danger' }}">Cash Out</a>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('cash-transactions.create', ['type' => 'cash_in']) }}" class="btn btn-success btn-sm">
                <i class="bi bi-plus-lg"></i> Cash In
            </a>
            <a href="{{ route('cash-transactions.create', ['type' => 'cash_out']) }}" class="btn btn-danger btn-sm">
                <i class="bi bi-dash-lg"></i> Cash Out
            </a>
        </div>
    </div>

    <div class="table-container">
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th style="text-align: right;">Amount (PKR)</th>
                    <th>Notes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $t)
                    <tr>
                        <td>{{ $t->transaction_date->format('d M Y') }}</td>
                        <td>
                            @if($t->isCashIn())
                                <span class="badge bg-success">Cash In</span>
                            @else
                                <span class="badge bg-danger">Cash Out</span>
                            @endif
                        </td>
                        <td><strong>{{ $t->category }}</strong></td>
                        <td>{{ ucfirst($t->payment_method) }}</td>
                        <td>{{ $t->reference_no ?: '—' }}</td>
                        <td style="text-align: right; font-weight: 600;"
                            class="{{ $t->isCashIn() ? 'text-success' : 'text-danger' }}">
                            {{ $t->isCashIn() ? '+' : '−' }}{{ money($t->amount) }}
                        </td>
                        <td>{{ $t->notes ?: '—' }}</td>
                        <td>
                            <a href="{{ route('cash-transactions.edit', $t) }}" class="btn btn-primary btn-sm">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No cash transactions in this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $transactions->links() }}
</div>
@endsection
