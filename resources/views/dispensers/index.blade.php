@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between mb-3">

    <h2>Dispensers</h2>

    <a href="{{ route('dispensers.create') }}"
       class="btn btn-primary">
        Add Dispenser
    </a>

</div>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

<table class="table table-bordered">

    <thead>
        <tr>
            <th>ID</th>
            <th>Code</th>
            <th>Company</th>
            <th>Model</th>
            <th>Nozzles</th>
            <th>Status</th>
            <th width="180">Action</th>
        </tr>
    </thead>

    <tbody>

        @forelse($dispensers as $dispenser)

        <tr>
            <td>{{ $dispenser->id }}</td>
            <td>{{ $dispenser->dispenser_code }}</td>
            <td>{{ $dispenser->company }}</td>
            <td>{{ $dispenser->model }}</td>
            <td>{{ $dispenser->nozzles_count }}</td>
            <td>{{ $dispenser->status ? 'Active' : 'Inactive' }}</td>

            <td>

                <a href="{{ route('dispensers.edit', $dispenser->id) }}"
                   class="btn btn-warning btn-sm">
                    Edit
                </a>

                <form action="{{ route('dispensers.destroy', $dispenser->id) }}"
                      method="POST"
                      class="d-inline">

                    @csrf
                    @method('DELETE')

                    <button class="btn btn-danger btn-sm"
                            onclick="return confirm('Delete dispenser?')">
                        Delete
                    </button>

                </form>

            </td>

        </tr>

        @empty
            <tr>
                <td colspan="7" class="text-center">No dispensers found</td>
            </tr>
        @endforelse

    </tbody>

</table>

@endsection