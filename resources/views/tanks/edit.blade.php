@extends('layouts.app')

@section('content')

<h2 class="mb-4">Edit Tank</h2>

<form action="{{ route('tanks.update', $tank->id) }}"
      method="POST">

    @csrf
    @method('PUT')


    <div class="mb-3">

        <label class="form-label">Product</label>

        <select name="product_id"
                class="form-control">

            @foreach($products as $product)

                <option
                    value="{{ $product->id }}"
                    {{ $tank->product_id == $product->id ? 'selected' : '' }}
                >
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
            value="{{ $tank->tank_number }}"
        >

    </div>



    <div class="mb-3">

        <label class="form-label">
            Capacity
        </label>

        <input
            type="number"
            step="0.01"
            name="capacity_liters"
            class="form-control"
            value="{{ $tank->capacity_liters }}"
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
            value="{{ $tank->current_stock_liters }}"
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
            value="{{ $tank->minimum_level }}"
        >

    </div>



    <div class="form-check mb-3">

        <input
            type="checkbox"
            name="status"
            class="form-check-input"
            {{ $tank->status ? 'checked' : '' }}
        >

        <label class="form-check-label">
            Active
        </label>

    </div>



    <button type="submit"
            class="btn btn-primary">
        Update
    </button>

    <a href="{{ route('tanks.index') }}"
       class="btn btn-secondary">
        Back
    </a>

</form>

@endsection