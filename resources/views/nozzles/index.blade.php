@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between mb-3">

    <h2>Nozzles</h2>

    <a href="{{ route('nozzles.create') }}" class="btn btn-primary">
        Add Nozzle
    </a>

</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<table class="table table-bordered">

    <thead>
        <tr>
            <th>ID</th>
            <th>Nozzle</th>
            <th>Dispenser</th>
            <th>Tank</th>
            <th>Product</th>
            <th>Meter</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>

    <tbody>

        @forelse($nozzles as $nozzle)

        <tr>
            <td>{{ $nozzle->id }}</td>
            <td>{{ $nozzle->nozzle_number }}</td>
            <td>{{ $nozzle->dispenser->dispenser_code }}</td>
            <td>{{ $nozzle->tank->tank_number }}</td>
            <td>{{ $nozzle->product->name }}</td>
            <td>{{ $nozzle->current_meter_reading }}</td>
            <td>{{ $nozzle->status ? 'Active' : 'Inactive' }}</td>

            <td>
                <a href="{{ route('nozzles.edit', $nozzle->id) }}" class="btn btn-warning btn-sm">
                    Edit
                </a>

                <form method="POST"
                      action="{{ route('nozzles.destroy', $nozzle->id) }}"
                      class="d-inline">

                    @csrf
                    @method('DELETE')

                    <button class="btn btn-danger btn-sm"
                            onclick="return confirm('Delete nozzle?')">
                        Delete
                    </button>

                </form>
            </td>
        </tr>

        @empty
            <tr>
                <td colspan="8" class="text-center">No nozzles found</td>
            </tr>
        @endforelse

    </tbody>

</table>

@endsection