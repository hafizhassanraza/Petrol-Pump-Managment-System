@extends('layouts.app')

@section('content')

<h2>Close Shift</h2>


@if(session('error'))

    <div class="alert alert-danger">
        {{ session('error') }}
    </div>

@endif


<div class="card mb-4">

    <div class="card-body">

        <p>
            <strong>Employee:</strong>
            {{ $shift->employee->name }}
        </p>

        <p>
            <strong>Nozzle:</strong>
            {{ $shift->nozzle->nozzle_number }}
        </p>

        <p>
            <strong>Product:</strong>
            {{ $shift->nozzle->product->name }}
        </p>

        <p>
            <strong>Opening Reading:</strong>
            {{ $shift->opening_reading }}
        </p>

    </div>

</div>


<form method="POST">

    @csrf


    <div class="mb-3">

        <label>Closing Reading</label>

        <input type="number"
               step="0.01"
               name="closing_reading"
               class="form-control"
               required>

    </div>



    <div class="mb-3">

        <label>Testing Liters</label>

        <input type="number"
               step="0.01"
               name="testing_liters"
               class="form-control"
               value="0">

    </div>



    <div class="mb-3">

        <label>Cash Received</label>

        <input type="number"
               step="0.01"
               name="cash_received"
               class="form-control"
               required>

    </div>



    <button class="btn btn-success">

        Submit Shift

    </button>


    <a href="{{ route('employee-shifts.index') }}"
       class="btn btn-secondary">

        Back

    </a>

</form>

@endsection