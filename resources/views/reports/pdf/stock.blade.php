@extends('reports.pdf.layout')

@section('title')
Tank Stock Report
@endsection

@section('content')

<table>

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