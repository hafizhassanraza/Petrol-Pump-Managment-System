@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between mb-3">

    <h2>Tanks</h2>

    <a href="{{ route('tanks.create') }}"
       class="btn btn-primary">
        Add Tank
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
            <th>Product</th>
            <th>Tank No</th>
            <th>Capacity</th>
            <th>Current Stock</th>
            <th>Minimum Level</th>
            <th>Status</th>
            <th width="180">Action</th>
        </tr>

    </thead>

    <tbody>

        @forelse($tanks as $tank)

            <tr>

                <td>{{ $tank->id }}</td>

                <td>{{ $tank->product->name }}</td>

                <td>{{ $tank->tank_number }}</td>

                <td>{{ $tank->capacity_liters }}</td>

                <td>{{ $tank->current_stock_liters }}</td>

                <td>{{ $tank->minimum_level }}</td>

                <td>
                    {{ $tank->status ? 'Active' : 'Inactive' }}
                </td>

                <td>

                    <a href="{{ route('tanks.edit', $tank->id) }}"
                       class="btn btn-sm btn-warning">
                        Edit
                    </a>

                    <form
                        action="{{ route('tanks.destroy', $tank->id) }}"
                        method="POST"
                        class="d-inline"
                    >

                        @csrf
                        @method('DELETE')

                        <button
                            type="submit"
                            class="btn btn-sm btn-danger"
                            onclick="return confirm('Delete this tank?')"
                        >
                            Delete
                        </button>

                    </form>

                </td>

            </tr>

        @empty

            <tr>
                <td colspan="8" class="text-center">
                    No tanks found.
                </td>
            </tr>

        @endforelse

    </tbody>

</table>

@endsection