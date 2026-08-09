@extends('layouts.app')

@section('content')
<div class="page-card">
    <div class="list-toolbar">
        <a href="{{ route('agency-customers.create') }}" class="btn btn-success btn-sm">
            <i class="bi bi-plus-lg"></i> Add Agency Customer
        </a>
    </div>
    <div class="table-container">
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Total Credit</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $c)
                    @php
                        $total = (float) ($c->credit_total ?? 0);
                        $paid = (float) ($c->paid_total ?? 0);
                        $balance = round($total - $paid, 2);
                    @endphp
                    <tr>
                        <td><strong>{{ $c->name }}</strong></td>
                        <td>{{ $c->phone ?? '—' }}</td>
                        <td>{{ money($total) }}</td>
                        <td>{{ money($paid) }}</td>
                        <td class="{{ $balance > 0 ? 'text-danger' : '' }}">{{ money($balance) }}</td>
                        <td>
                            @if($c->status)
                                <span class="status-active">Active</span>
                            @else
                                <span class="status-inactive">Inactive</span>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            <a href="{{ route('agency-customers.show', $c) }}" class="btn btn-primary btn-sm">Credits</a>
                            <a href="{{ route('agency-customers.edit', $c) }}" class="btn btn-warning btn-sm">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No agency customers yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $customers->links() }}
</div>
@endsection
