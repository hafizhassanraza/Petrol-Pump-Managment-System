@extends('layouts.app')

@section('content')

<h2>Tank Variance Report</h2>

<div>

    <a href="{{ route('reports.variance.pdf') }}"
    class="btn btn-danger">

        Download PDF
    </a>

</div>


<table class="table table-bordered">

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