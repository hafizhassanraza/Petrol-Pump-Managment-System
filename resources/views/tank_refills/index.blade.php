@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between mb-3">

    <h2>Tank Refills</h2>

    <a href="{{ route('tank-refills.create') }}"
       class="btn btn-primary">

        Add Refill

    </a>

</div>


@if(session('success'))

    <div class="alert alert-success">
        {{ session('success') }}
    </div>

@endif


<table class="table table-bordered">

    <thead>

        <tr>

            <th>ID</th>

            <th>Tank</th>

            <th>Product</th>

            <th>Invoice</th>

            <th>Liters</th>

            <th>Purchase Rate</th>

            <th>Total Amount</th>

            <th>Date</th>

        </tr>

    </thead>

    <tbody>

        @foreach($refills as $refill)

            <tr>

                <td>{{ $refill->id }}</td>

                <td>{{ $refill->tank->tank_number }}</td>

                <td>{{ $refill->product->name }}</td>

                <td>{{ $refill->invoice_no }}</td>

                <td>{{ $refill->quantity_liters }}</td>

                <td>{{ $refill->purchase_rate }}</td>

                <td>{{ $refill->total_amount }}</td>

                <td>{{ $refill->received_datetime }}</td>

            </tr>

        @endforeach

    </tbody>

</table>

@endsection