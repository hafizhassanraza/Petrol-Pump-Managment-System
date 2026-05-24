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



{{-- <div class="d-flex justify-content-between mb-3">

    <h2>Nozzles</h2>

    <a href="{{ route('nozzles.create') }}" class="btn btn-primary">
        Add Nozzle
    </a>

</div> --}}

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="page-card">

    <div class="d-flex justify-content-between align-items-center mb-2">

        <div>
            <h4>
                Nozzles
            </h4>
        </div>

        <div>
            <span class="badge bg-success px-3 py-2 rounded-pill">
                Total Nozzles: {{ $nozzles->count() }}
            </span>
        </div>

    </div>


    @if(session('success'))

        <div class="alert alert-success border-0 rounded-4">
            {{ session('success') }}
        </div>

    @endif


    <div class="table-responsive">

        <table class="table custom-table align-middle">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nozzle</th>
                    <th>Dispenser</th>
                    <th>Tank</th>
                    <th>Product</th>
                    <th>Meter</th>
                    <th>Status</th>
                    {{-- <th>Action</th> --}}
                </tr>
            </thead>

            <tbody>

                @forelse($nozzles as $nozzle)

                <tr>
                    <td class="fw-semibold">{{ $nozzle->id }}</td>
                    <td class="fw-semibold">{{ $nozzle->nozzle_number }}</td>
                    <td class="fw-semibold">
                        <span class="dispanser-badge">{{ $nozzle->dispenser->dispenser_code }}</span>
                    </td>
                    <td class="fw-semibold">
                        <span class="tank-badge">{{ $nozzle->tank->tank_number }}</span>
                    </td>
                    <td class="fw-semibold">
                        <span class="product-badge">{{ $nozzle->product->name }}</span>
                    </td>
                    <td class="fw-semibold">{{ $nozzle->current_meter_reading }}</td>
                    <td class="fw-semibold">
                        @if($nozzle->status)
                            <span class="status-active">Active</span>
                        @else   
                            <span class="status-inactive">Inactive</span>
                        @endif
                    </td>

                </tr>

                @empty
                    <tr>
                        <td colspan="8" class="text-center">No nozzles found</td>
                    </tr>
                @endforelse

            </tbody>

    {{--         <thead>
                <tr>
                    <th>ID</th>
                    <th>Nozzle</th>
                    <th>Dispenser</th>
                    <th>Tank</th>
                    <th>Product</th>
                    <th>Meter</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

                @forelse($nozzles as $nozzle)

                <tr>
                    <td>{{ $nozzle->id }}</td>
                    <td>{{ $nozzle->nozzle_number }}</td>
                    <td>{{ $nozzle->dispenser->dispenser_code }}</td>
                    <td>{{ $nozzle->tank->tank_number }}</td>
                    <td>{{ $nozzle->product->name }}</td>
                    <td>{{ $nozzle->current_meter_reading }}</td>
                    <td>{{ $nozzle->status ? 'Active' : 'Inactive' }}</td>

                    <td>
                        <a href="{{ route('nozzles.edit', $nozzle->id) }}" class="btn btn-warning btn-sm">
                            Edit
                        </a>

                        <form method="POST"
                            action="{{ route('nozzles.destroy', $nozzle->id) }}"
                            class="d-inline">

                            @csrf
                            @method('DELETE')

                            <button class="btn btn-danger btn-sm"
                                    onclick="return confirm('Delete nozzle?')">
                                Delete
                            </button>

                        </form>
                    </td>
                </tr>

                @empty
                    <tr>
                        <td colspan="8" class="text-center">No nozzles found</td>
                    </tr>
                @endforelse

            </tbody> --}}

        </table>
    </div>
</div>

@endsection