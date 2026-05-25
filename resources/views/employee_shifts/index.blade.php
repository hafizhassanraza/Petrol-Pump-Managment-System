@extends('layouts.app')

@section('content')

<style>

    body{
        background: #f4f7f9;
        font-family: 'Inter', sans-serif;
    }

    .page-card{
        background: #ffffff;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        border: 1px solid #edf1f5;
    }

    .page-title{
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 4px;
    }

    .page-subtitle{
        color: #6b7280;
        font-size: 14px;
    }

    .custom-table{
        
        border-spacing: 0 0px;
    }

    .custom-table thead th{
        border: none;
        background: #198754;
        color: #fff;
        padding: 16px;
        font-size: 14px;
        font-weight: 600;
        white-space: nowrap;
        text-align: center;
        vertical-align: middle;
    }

    .custom-table thead th:first-child{
        border-top-left-radius: 12px;
        border-bottom-left-radius: 0px;
    }

    .custom-table thead th:last-child{
        border-top-right-radius: 12px;
        border-bottom-right-radius: 0px;
    }

    .custom-table tbody tr:nth-child(odd) td{
        background: #ffffff;
    }

    .custom-table tbody tr:nth-child(even) td{
        background: #f2fbf5;
    }

    .custom-table tbody tr:hover td{
        background: #ecfdf3 !important;
        transition: 0.2s ease;
        
    }


    /* .custom-table tbody td{
        padding: 18px 16px;
        vertical-align: middle;
        border-top: 1px solid #f1f3f5;
        border-bottom: 1px solid #f1f3f5;
        font-size: 14px;
        color: #6b7280;
    } */

    .custom-table tbody td{
        padding: 18px 16px;
        vertical-align: middle;
        text-align: center;
        border: 1px solid #d1d5db;
        font-size: 14px;
        color: #6b7280;
    }

    .custom-table tbody td .d-flex{
        justify-content: center;
    }

    /* .custom-table tbody td:first-child{
        border-left: 1px solid #f1f3f5;
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
    }

    .custom-table tbody td:last-child{
        border-right: 1px solid #f1f3f5;
        border-top-right-radius: 14px;
        border-bottom-right-radius: 28px;
    } */

    .custom-table tbody td:first-child{
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
    }

    .custom-table tbody td:last-child{
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
    }

    .tank-badge{
        background: #6b7280;
        color: #fff;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
    }

    .dispanser-badge{
        background: green;
        color: #fff;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
    }

    .product-badge{
        background: skyblue;
        color: #1f2937;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
    }



    .status-active{
        background: #dcfce7;
        color: #15803d;
        padding: 6px 14px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-submitted{
        background: #fef3c7;
        color: #92400e;
        padding: 6px 14px;
        border-radius: 6px;
        font-size: 12px;
    }
    .status-verified{
        background: #dcfce7;
        color: #15803d;
        padding: 6px 14px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
    }

    .status-inactive{
        background: #fee2e2;
        color: #dc2626;
        padding: 6px 14px;
        border-radius: 60px;
        font-size: 12px;
        font-weight: 600;
    }

    .progress{
        height: 8px;
        border-radius: 30px;
        background: gainsboro;
    }

    .progress-bar{
        border-radius: 30px;
    }



</style>



@if(session('success'))

    <div class="alert alert-success" id="success-alert">
        {{ session('success') }}
    </div>

@endif


<script>

    setTimeout(function () {

        let alertBox = document.getElementById('success-alert');

        if(alertBox){
            alertBox.style.transition = "0.5s";
            alertBox.style.opacity = "0";
            
            setTimeout(() => {
                alertBox.remove();
            }, 500);
        }

    }, 1000);

</script>


<div class="page-card">

    <div class="d-flex justify-content-between align-items-center mb-2">

        <div>
            <h4>
                Employee Shifts
            </h4>
        </div>

        <div>
            
            <a href="{{ route('employee-shifts.create') }}" class="btn btn-primary">
                Add New Shift
            </a>
        </div>

    </div>



    <div class="table-responsive">

        <table class="table custom-table align-middle">

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
                    <th>Online</th>
                    <th>Shortage</th>
                    <th>Extra</th>
                    <th>Status</th>
                    <th width="220">Action</th>
                </tr>

            </thead>

            <tbody>

                @forelse($shifts as $shift)

                <tr>

                    <td class="fw-semibold">{{ $shift->id }}</td>
                    <td class="fw-semibold">{{ $shift->employee->name ?? '-' }}</td>
                    <td class="fw-semibold"> <span class="product-badge">{{ $shift->nozzle->nozzle_number ?? '-' }}</span></td>
                    <td class="fw-semibold"> <span class="tank-badge">{{ $shift->shift->name ?? '-' }}</span></td>
                    <td class="fw-semibold">{{ $shift->assigned_date }}</td>
                    <td class="fw-semibold">{{ $shift->opening_reading }}</td>
                    <td class="fw-semibold">{{ $shift->closing_reading ?? '-' }}</td>
                    <td class="fw-semibold">{{ $shift->total_liters ?? '-' }}</td>
                    <td class="fw-semibold">{{ $shift->total_amount ?? '-' }}</td>
                    <td class="fw-semibold">{{ $shift->cash_received ?? '-' }}</td>
                    <td class="fw-semibold">{{ $shift->online_received ?? '-' }}</td>
                    <td class="fw-semibold">
                        <span class="text-danger">
                            {{ $shift->shortage_amount ?? 0 }}
                        </span>
                    </td>
                    <td class="fw-semibold">
                        <span class="text-success">
                            {{ $shift->extra_amount ?? 0 }}
                        </span>
                    </td>

                    <td class="fw-semibold">
                        @if($shift->status == 'active')
                            <span class="status-active">Active</span>
                        @elseif($shift->status == 'submitted') 
                            <span class="status-submitted">Submitted</span>
                        @elseif($shift->status == 'verified')
                            <span class="status-verified">Verified</span>
                        @endif
                    </td>


                    <td class="fw-semibold">

                        {{-- CLOSE SHIFT BUTTON --}}
                        @if($shift->status == 'active')

                            <a href="{{ route('employee-shifts.close-form', $shift->id) }}"
                            class="btn btn-danger btn-sm">

                                Close Shift

                            </a>

                        @endif


                        {{-- VIEW BUTTON (optional future) --}}
                        {{-- <a href="#"
                        class="btn btn-sm btn-info">
                            View
                        </a> --}}

                    </td>

                </tr>

                @empty

                <tr>
                    <td colspan="15" class="text-center">
                        No shifts assigned yet.
                    </td>
                </tr>

                @endforelse

            </tbody>

</table>

@endsection