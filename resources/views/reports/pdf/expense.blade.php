@extends('reports.pdf.layout')

@section('title')
Expense Report
@endsection

@section('content')

<table>

<thead>
<tr>
    <th>Type</th>
    <th>Amount</th>
    <th>Date</th>
</tr>
</thead>

<tbody>

@foreach($expenses as $e)

<tr>
    <td>{{ $e->expense_type }}</td>
    <td>{{ $e->amount }}</td>
    <td>{{ $e->expense_date }}</td>
</tr>

@endforeach

</tbody>

</table>

@endsection