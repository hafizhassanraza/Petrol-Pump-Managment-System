@extends('reports.pdf.layout')

@section('title')
Daily Sales Report
@endsection

@section('content')

<table>

<thead>
<tr>
    <th>Employee</th>
    <th>Nozzle</th>
    <th>Liters</th>
    <th>Amount</th>
    <th>Date</th>
</tr>
</thead>

<tbody>

@foreach($shifts as $s)

<tr>
    <td>{{ $s->employee->name ?? '' }}</td>
    <td>{{ $s->nozzle->nozzle_number ?? '' }}</td>
    <td>{{ $s->total_liters }}</td>
    <td>{{ $s->total_amount }}</td>
    <td>{{ $s->created_at->format('d-m-Y') }}</td>
</tr>

@endforeach

</tbody>

</table>

@endsection