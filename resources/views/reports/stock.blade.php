@extends('layouts.app')

@section('content')

<h2>Tank Stock Report</h2>
<div>
    <a href="{{ route('reports.stock.pdf') }}"
   class="btn btn-danger">

    Download PDF
</a>
</div>


<table class="table table-bordered">

    <thead>
        <tr>
            <th>Tank</th>
            <th>Product</th>
            <th>Capacity</th>
            <th>Current Stock</th>
        </tr>
    </thead>

    <tbody>

        @foreach($tanks as $t)

        <tr>
            <td>{{ $t->tank_number }}</td>
            <td>{{ $t->product->name }}</td>
            <td>{{ $t->capacity_liters }}</td>
            <td>{{ $t->current_stock_liters }}</td>
        </tr>

        @endforeach

    </tbody>

</table>

@endsection