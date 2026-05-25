@extends('layouts.app')

@section('content')

<div class="page-card">
    <h3 class="page-title">Create Tank</h3>
    <p class="page-subtitle">Add a storage tank and its initial details.</p>

    <form action="{{ route('tanks.store') }}"
        method="POST">

    @csrf


    <div class="mb-3">

        <label class="form-label">Product</label>

        <select name="product_id"
                class="form-control">

            <option value="">Select Product</option>

            @foreach($products as $product)

                <option value="{{ $product->id }}">
                    {{ $product->name }}
                </option>

            @endforeach

        </select>

    </div>



    <div class="mb-3">

        <label class="form-label">Tank Number</label>

        <input
            type="text"
            name="tank_number"
            class="form-control"
        >

    </div>



    <div class="mb-3">

        <label class="form-label">
            Capacity (Liters)
        </label>

        <input
            type="number"
            step="0.01"
            name="capacity_liters"
            class="form-control"
        >

    </div>



    <div class="mb-3">

        <label class="form-label">
            Current Stock
        </label>

        <input
            type="number"
            step="0.01"
            name="current_stock_liters"
            class="form-control"
        >

    </div>



    <div class="mb-3">

        <label class="form-label">
            Minimum Level
        </label>

        <input
            type="number"
            step="0.01"
            name="minimum_level"
            class="form-control"
        >

    </div>



    <div class="form-check mb-3">

        <input
            type="checkbox"
            name="status"
            class="form-check-input"
            checked
        >

        <label class="form-check-label">
            Active
        </label>

    </div>



    <button type="submit"
            class="btn btn-success">
        Save
    </button>

    <a href="{{ route('tanks.index') }}"
       class="btn btn-secondary">
        Back
    </a>

</form>

</div>

@endsection