@extends('layouts.app')

@section('content')
<div class="page-card">
    <div class="list-toolbar">
        <a href="{{ route('dispensers.create') }}" class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Add Dispenser</a>
    </div>
    <div class="table-container">
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Company</th>
                    <th>Model</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dispensers as $d)
                    <tr>
                        <td><strong>{{ $d->dispenser_code }}</strong></td>
                        <td>{{ $d->company }}</td>
                        <td>{{ $d->model }}</td>
                        <td>@if($d->status)<span class="status-active">Active</span>@else<span class="status-inactive">Inactive</span>@endif</td>
                        <td><a href="{{ route('dispensers.edit', $d) }}" class="btn btn-warning btn-sm">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No dispensers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $dispensers->links() }}
</div>
@endsection
