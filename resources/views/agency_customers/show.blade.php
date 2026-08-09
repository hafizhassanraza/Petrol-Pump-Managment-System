@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h3 class="page-title mb-1">{{ $customer->name }}</h3>
            <p class="page-subtitle mb-0">
                {{ $customer->phone ?? 'No phone' }}
                @if($customer->address) · {{ $customer->address }} @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('agency-customers.edit', $customer) }}" class="btn btn-warning btn-sm">Edit</a>
            <a href="{{ route('agency-customers.index') }}" class="btn btn-secondary btn-sm">Back</a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="page-card">
    <h5 class="mb-3">Credit Entries (from shifts)</h5>
    <div class="table-container">
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Fuel</th>
                    <th>Liters</th>
                    <th>Rate</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Pay</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customer->credits as $credit)
                    <tr>
                        <td>{{ $credit->credit_datetime?->format('d M Y') }}</td>
                        <td>{{ $credit->product->name ?? '—' }}</td>
                        <td>{{ number_format($credit->liters, 2) }}</td>
                        <td>{{ rate($credit->price_per_liter) }}</td>
                        <td>{{ money($credit->total_amount) }}</td>
                        <td>{{ money($credit->paid_amount) }}</td>
                        <td class="{{ $credit->balance() > 0 ? 'text-danger fw-semibold' : '' }}">{{ money($credit->balance()) }}</td>
                        <td>
                            @if($credit->status === 'paid')
                                <span class="badge bg-success">Paid</span>
                            @elseif($credit->status === 'partial')
                                <span class="badge bg-warning text-dark">Partial</span>
                            @else
                                <span class="badge bg-danger">Open</span>
                            @endif
                        </td>
                        <td style="min-width: 280px;">
                            @if($credit->balance() > 0)
                                <form method="POST" action="{{ route('agency-customers.credits.pay', $credit) }}" class="row g-1 align-items-end">
                                    @csrf
                                    <div class="col-5">
                                        <input type="number" step="0.01" min="0.01" max="{{ $credit->balance() }}"
                                               name="amount" class="form-control form-control-sm"
                                               placeholder="Amount" required>
                                    </div>
                                    <div class="col-4">
                                        <select name="payment_method" class="form-control form-control-sm" required>
                                            <option value="cash">Cash</option>
                                            <option value="online">Online</option>
                                        </select>
                                    </div>
                                    <div class="col-3">
                                        <button class="btn btn-success btn-sm w-100">Pay</button>
                                    </div>
                                    <div class="col-12">
                                        <input type="date" name="payment_date" class="form-control form-control-sm"
                                               value="{{ $defaultPaymentDate }}" required>
                                    </div>
                                    @if($credit->payments->isNotEmpty())
                                        <div class="col-12">
                                            <small class="text-muted">
                                                Installments:
                                                @foreach($credit->payments as $p)
                                                    {{ money($p->amount) }} ({{ $p->payment_date->format('d M') }}{{ !$loop->last ? ',' : '' }})
                                                @endforeach
                                            </small>
                                        </div>
                                    @endif
                                </form>
                            @else
                                <small class="text-muted">
                                    @forelse($credit->payments as $p)
                                        {{ money($p->amount) }} · {{ $p->payment_date->format('d M Y') }}{{ !$loop->last ? '; ' : '' }}
                                    @empty
                                        —
                                    @endforelse
                                </small>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            No credit entries yet. Add agency fuel when closing an employee shift.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
