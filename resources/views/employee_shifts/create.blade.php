@extends('layouts.app')

@section('content')

<h2>Assign Employee Shift</h2>

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

        <select name="nozzle_id"
                class="form-control"
                required>

            <option value="">
                Select Nozzle
            </option>

            @foreach($nozzles as $nozzle)

                <option value="{{ $nozzle->id }}">

                    {{ $nozzle->nozzle_number }}
                    -
                    {{ $nozzle->product->name ?? '' }}

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

        <input type="number"
               step="0.01"
               name="opening_reading"
               class="form-control"
               required>

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

@endsection