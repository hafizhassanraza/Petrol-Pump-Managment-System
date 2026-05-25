@extends('layouts.app')

@section('content')

<div class="page-card">
    <h3 class="page-title">Add Tank Refill</h3>
    <p class="page-subtitle">Record a new tank refill entry.</p>

<form method="POST"
      action="{{ route('tank-refills.store') }}">

    @csrf


    <div class="mb-3">

        <label>Tank</label>

        <select name="tank_id"
                class="form-control"
                required>

            <option value="">
                Select Tank
            </option>

            @foreach($tanks as $tank)

                <option value="{{ $tank->id }}">

                    {{ $tank->tank_number }}

                </option>

            @endforeach

        </select>

    </div>



    <div class="mb-3">

        <label>Product</label>

        <select name="product_id"
                class="form-control"
                required>

            <option value="">
                Select Product
            </option>

            @foreach($products as $product)

                <option value="{{ $product->id }}">

                    {{ $product->name }}

                </option>

            @endforeach

        </select>

    </div>



    <div class="mb-3">

        <label>Invoice Number</label>

        <input type="text"
               name="invoice_no"
               class="form-control">

    </div>



    <div class="mb-3">

        <label>Quantity (Liters)</label>

        <input type="number"
               step="0.01"
               name="quantity_liters"
               class="form-control"
               required>

    </div>



    <div class="mb-3">

        <label>Purchase Rate</label>

        <input type="number"
               step="0.01"
               name="purchase_rate"
               class="form-control"
               required>

    </div>



    <div class="mb-3">

        <label>Notes</label>

        <textarea name="notes"
                  class="form-control"></textarea>

    </div>



    <button class="btn btn-success">

        Save Refill

    </button>

</form>

</div>

@endsection