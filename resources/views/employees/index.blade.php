@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between mb-3">

    <h2>Employees</h2>

    <a href="{{ route('employees.create') }}" class="btn btn-primary">
        Add Employee
    </a>

</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<table class="table table-bordered">

    <thead>
        <tr>
            <th>ID</th>
            <th>Code</th>
            <th>Name</th>
            <th>Phone</th>
            <th>Salary</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>

    <tbody>

        @foreach($employees as $e)

        <tr>
            <td>{{ $e->id }}</td>
            <td>{{ $e->employee_code }}</td>
            <td>{{ $e->name }}</td>
            <td>{{ $e->phone }}</td>
            <td>{{ $e->salary }}</td>
            <td>{{ $e->status ? 'Active' : 'Inactive' }}</td>

            <td>
                <a href="{{ route('employees.edit', $e->id) }}" class="btn btn-warning btn-sm">
                    Edit
                </a>

                <form method="POST"
                      action="{{ route('employees.destroy', $e->id) }}"
                      class="d-inline">

                    @csrf
                    @method('DELETE')

                    <button class="btn btn-danger btn-sm">
                        Delete
                    </button>

                </form>
            </td>
        </tr>

        @endforeach

    </tbody>

</table>

@endsection