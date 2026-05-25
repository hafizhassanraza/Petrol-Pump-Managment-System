@extends('layouts.app')

@section('content')
<div class="page-card">
    <div class="list-toolbar">
        <a href="{{ route('employees.create') }}" class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Add Employee</a>
    </div>
    <div class="table-container">
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Salary</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $e)
                    <tr>
                        <td><strong>{{ $e->employee_code }}</strong></td>
                        <td>{{ $e->name }}</td>
                        <td>{{ $e->phone ?? '—' }}</td>
                        <td>{{ number_format($e->salary, 0) }}</td>
                        <td>@if($e->status)<span class="status-active">Active</span>@else<span class="status-inactive">Inactive</span>@endif</td>
                        <td>
                            <a href="{{ route('employees.edit', $e) }}" class="btn btn-warning btn-sm">Edit</a>
                            <form action="{{ route('employees.destroy', $e) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete employee?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No employees found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $employees->links() }}
</div>
@endsection
