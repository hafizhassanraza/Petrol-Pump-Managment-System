@extends('reports.pdf.layout')

@section('title')
Tank Variance Report
@endsection

@section('content')

<table>

<thead>
<tr>
    <th>Tank</th>
    <th>System Stock</th>
    <th>Physical Stock</th>
    <th>Difference</th>
</tr>
</thead>

<tbody>

@foreach($variances as $v)

<tr>
    <td>{{ $v['tank'] }}</td>
    <td>{{ $v['system'] }}</td>
    <td>{{ $v['physical'] }}</td>
    <td>{{ $v['difference'] }}</td>
</tr>

@endforeach

</tbody>

</table>

@endsection