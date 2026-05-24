@extends('layouts.app')

@section('content')

<h2>Tank Dip Reading</h2>

<form method="POST"
      action="{{ route('tank-dip-readings.store') }}">

    @csrf


    <div class="mb-3">

        <label>Tank</label>

        <select name="tank_id"
                class="form-control">

            @foreach($tanks as $tank)

                <option value="{{ $tank->id }}">

                    {{ $tank->tank_number }}

                    (Stock:
                    {{ $tank->current_stock_liters }})

                </option>

            @endforeach

        </select>

    </div>



    <div class="mb-3">

        <label>Measured Liters</label>

        <input type="number"
               step="0.01"
               name="measured_liters"
               class="form-control">

    </div>



    <button class="btn btn-success">

        Save Reading

    </button>

</form>

@endsection