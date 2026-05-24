@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between mb-3">

    <h2>Products</h2>

    <a href="{{ route('products.create') }}"
       class="btn btn-primary">
        Add Product
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
            <th>Name</th>
            <th>Unit</th>
            <th>Status</th>
            <th width="180">Action</th>
        </tr>

    </thead>

    <tbody>

        @forelse($products as $product)

            <tr>

                <td>{{ $product->id }}</td>

                <td>{{ $product->name }}</td>

                <td>{{ $product->unit }}</td>

                <td>
                    {{ $product->status ? 'Active' : 'Inactive' }}
                </td>

                <td>

                    <a href="{{ route('products.edit', $product->id) }}"
                       class="btn btn-sm btn-warning">
                        Edit
                    </a>

                    <form
                        action="{{ route('products.destroy', $product->id) }}"
                        method="POST"
                        class="d-inline"
                    >

                        @csrf
                        @method('DELETE')

                        <button
                            type="submit"
                            class="btn btn-sm btn-danger"
                            onclick="return confirm('Delete this product?')"
                        >
                            Delete
                        </button>

                    </form>

                </td>

            </tr>

        @empty

            <tr>
                <td colspan="5" class="text-center">
                    No products found.
                </td>
            </tr>

        @endforelse

    </tbody>

</table>

@endsection