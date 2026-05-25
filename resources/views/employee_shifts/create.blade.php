@extends('layouts.app')

@section('content')

<div class="page-card">
    <h3 class="page-title">Assign Employee Shift</h3>
    <p class="page-subtitle">Add a new shift for an employee.</p>

@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

<form method="POST"
      action="{{ route('employee-shifts.store') }}">

    @csrf


    {{-- EMPLOYEE --}}
    <div class="mb-3">

        <label>Employee</label>

        <select name="employee_id"
                class="form-control"
                required>

            <option value="">
                Select Employee
            </option>

            @foreach($employees as $employee)

                <option value="{{ $employee->id }}">
                    {{ $employee->name }}
                </option>

            @endforeach

        </select>

    </div>



    {{-- NOZZLE --}}
    <div class="mb-3">

        <label>Nozzle</label>

        <select name="nozzle_id" id="nozzle_id" class="form-control" required>
            <option value="" data-meter="0">Select Nozzle</option>
            @foreach($nozzles as $nozzle)
                <option value="{{ $nozzle->id }}" data-meter="{{ $nozzle->current_meter_reading }}">
                    {{ $nozzle->nozzle_number }} — {{ $nozzle->product->name ?? '' }}
                    (meter: {{ number_format($nozzle->current_meter_reading, 2) }})
                </option>
            @endforeach
        </select>

    </div>



    {{-- SHIFT --}}
    <div class="mb-3">

        <label>Shift</label>

        <select name="shift_id"
                class="form-control"
                required>

            <option value="">
                Select Shift
            </option>

            @foreach($shifts as $shift)

                <option value="{{ $shift->id }}">
                    {{ $shift->name }}
                </option>

            @endforeach

        </select>

    </div>



    {{-- OPENING READING --}}
    <div class="mb-3">

        <label>Opening Reading</label>

        <input type="number" step="0.01" name="opening_reading" id="opening_reading" class="form-control" required>
        <small class="text-muted">Should match or exceed current nozzle meter reading.</small>

    </div>



    <button type="submit"
            class="btn btn-success">

        Assign Shift

    </button>


    <a href="{{ route('employee-shifts.index') }}"
       class="btn btn-secondary">

        Back

    </a>

</form>

</div>

<script>
document.getElementById('nozzle_id').addEventListener('change', function () {
    const meter = this.selectedOptions[0]?.dataset.meter;
    if (meter) document.getElementById('opening_reading').value = meter;
});
</script>

@endsection