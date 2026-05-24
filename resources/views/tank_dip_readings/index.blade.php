@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between mb-3">

    <h2>Tank Dip Readings</h2>

    <a href="{{ route('tank-dip-readings.create') }}"
       class="btn btn-primary">

        Add Reading

    </a>

</div>


<table class="table table-bordered">

    <thead>

        <tr>

            <th>ID</th>

            <th>Tank</th>

            <th>Measured Liters</th>

            <th>Difference</th>

            <th>Date</th>

        </tr>

    </thead>

    <tbody>

        @foreach($readings as $reading)

            <tr>

                <td>{{ $reading->id }}</td>

                <td>{{ $reading->tank->tank_number }}</td>

                <td>{{ $reading->measured_liters }}</td>

                <td>{{ $reading->notes }}</td>

                <td>{{ $reading->reading_datetime }}</td>

            </tr>

        @endforeach

    </tbody>

</table>

@endsection