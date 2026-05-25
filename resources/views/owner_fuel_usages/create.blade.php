@extends('layouts.app')

@section('content')

<h2>Owner Fuel Usage</h2>

@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

<form method="POST"
      action="{{ route('owner-fuel-usages.store') }}">

    @csrf


    {{-- product removed: derived from selected nozzle --}}



    <div class="mb-3">

        <label>Nozzle</label>

        <select name="nozzle_id"
                class="form-control">

            @foreach($nozzles as $n)

                <option value="{{ $n->id }}">{{ $n->nozzle_number }} - {{ $n->product->name ?? '-' }}</option>

            @endforeach

        </select>

    </div>



    <div class="mb-3">

        <label>Person Name</label>

        <input type="text"
               name="person_name"
               class="form-control">

    </div>



    <div class="mb-3">

        <label>Vehicle No</label>

        <input type="text"
               name="vehicle_no"
               class="form-control">

    </div>



    <div class="mb-3">

        <label>Purpose</label>

        <input type="text"
               name="purpose"
               class="form-control">

    </div>



    <div class="mb-3">

        <label>Liters</label>

        <input type="number"
               step="0.01"
               name="liters"
               class="form-control">

    </div>



    <div class="mb-3">

        <label>Notes</label>

        <textarea name="notes"
                  class="form-control"></textarea>

    </div>



    <button class="btn btn-success">

        Save Usage

    </button>

</form>

@endsection