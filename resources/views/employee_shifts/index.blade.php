@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between mb-3">

    <h2>Employee Shifts</h2>

    <a href="{{ route('employee-shifts.create') }}" class="btn btn-primary">
        Assign Shift
    </a>

</div>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif


<table class="table table-bordered">

    <thead class="table-dark">

        <tr>
            <th>ID</th>
            <th>Employee</th>
            <th>Nozzle</th>
            <th>Shift</th>
            <th>Date</th>
            <th>Opening</th>
            <th>Closing</th>
            <th>Liters</th>
            <th>Amount</th>
            <th>Cash</th>
            <th>Shortage</th>
            <th>Extra</th>
            <th>Status</th>
            <th width="220">Action</th>
        </tr>

    </thead>

    <tbody>

        @forelse($shifts as $shift)

        <tr>

            <td>{{ $shift->id }}</td>

            <td>{{ $shift->employee->name ?? '-' }}</td>

            <td>{{ $shift->nozzle->nozzle_number ?? '-' }}</td>

            <td>{{ $shift->shift->name ?? '-' }}</td>

            <td>{{ $shift->assigned_date }}</td>

            <td>{{ $shift->opening_reading }}</td>

            <td>{{ $shift->closing_reading ?? '-' }}</td>

            <td>{{ $shift->total_liters ?? '-' }}</td>

            <td>{{ $shift->total_amount ?? '-' }}</td>

            <td>{{ $shift->cash_received ?? '-' }}</td>

            <td>
                <span class="text-danger">
                    {{ $shift->shortage_amount ?? 0 }}
                </span>
            </td>

            <td>
                <span class="text-success">
                    {{ $shift->extra_amount ?? 0 }}
                </span>
            </td>

            <td>

                @if($shift->status == 'active')
                    <span class="badge bg-primary">Active</span>

                @elseif($shift->status == 'submitted')
                    <span class="badge bg-warning text-dark">Submitted</span>

                @elseif($shift->status == 'verified')
                    <span class="badge bg-success">Verified</span>

                @endif

            </td>

            <td>

                {{-- CLOSE SHIFT BUTTON --}}
                @if($shift->status == 'active')

                    <a href="{{ route('employee-shifts.close-form', $shift->id) }}"
                    class="btn btn-danger btn-sm">

                        Close Shift

                    </a>

                @endif


                {{-- VIEW BUTTON (optional future) --}}
                <a href="#"
                   class="btn btn-sm btn-info">
                    View
                </a>

            </td>

        </tr>

        @empty

        <tr>
            <td colspan="14" class="text-center">
                No shifts assigned yet.
            </td>
        </tr>

        @endforelse

    </tbody>

</table>

@endsection