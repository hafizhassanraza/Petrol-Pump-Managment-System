@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between mb-3">

    <h2>Owner Fuel Usage</h2>

    <a href="{{ route('owner-fuel-usages.create') }}"
       class="btn btn-primary">

        Add Usage

    </a>

</div>


<table class="table table-bordered">

    <thead>

        <tr>

            <th>ID</th>

            <th>Product</th>

            <th>Nozzle</th>

            <th>Person</th>

            <th>Vehicle</th>

            <th>Liters</th>

            <th>Total</th>

            <th>Date</th>

        </tr>

    </thead>

    <tbody>

        @foreach($usages as $u)

            <tr>

                <td>{{ $u->id }}</td>

                <td>{{ $u->product->name }}</td>

                <td>{{ $u->nozzle->nozzle_number }}</td>

                <td>{{ $u->person_name }}</td>

                <td>{{ $u->vehicle_no }}</td>

                <td>{{ $u->liters }}</td>

                <td>{{ $u->total_amount }}</td>

                <td>{{ $u->usage_datetime }}</td>

            </tr>

        @endforeach

    </tbody>

</table>

@endsection