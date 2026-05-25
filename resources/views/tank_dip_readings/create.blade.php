@extends('layouts.app')

@section('content')

<div class="page-card">
    <h3 class="page-title">Add Tank Dip Reading</h3>
    <p class="page-subtitle">Record dip readings for a tank.</p>

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
        <label>Physical Stock (Liters) *</label>
        <input type="number" step="0.01" min="0" name="measured_liters" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Notes</label>
        <textarea name="notes" class="form-control" rows="2"></textarea>
    </div>

    <div class="form-check mb-3">
        <input type="checkbox" name="reconcile_stock" value="1" class="form-check-input" id="reconcile">
        <label class="form-check-label" for="reconcile">
            Update system stock to match physical reading (reconcile)
        </label>
    </div>

    <button class="btn btn-success">

        Save Reading

    </button>

</form>

</div>

@endsection