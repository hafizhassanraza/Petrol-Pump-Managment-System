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


<div class="page-card">

    <div class="d-flex justify-content-between align-items-center mb-2">

        <div>
            <h4>
                Fuel Tanks
            </h4>

        </div>

        <div>
            <span class="badge bg-success px-3 py-2 rounded-pill">
                Total Tanks: {{ $tanks->count() }}
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
                    <th>Sr. #</th>
                    <th>Product</th>
                    <th>Tank No</th>
                    <th>Capacity</th>
                    <th>Current Stock</th>
                    <th>Min Level</th>
                    <th>Status</th>
                    <th width="180">Fill %</th>
                </tr>

            </thead>

            <tbody>

                @forelse($tanks as $tank)

                    @php
                        $percentage = $tank->capacity_liters > 0
                            ? ($tank->current_stock_liters / $tank->capacity_liters) * 100
                            : 0;
                    @endphp

                    <tr>

                        <td class="fw-semibold">
                            {{ $tank->id }}
                        </td>

                        <td>
                            <div class="fw-semibold">
                                {{ $tank->product->name }}
                            </div>
                        </td>

                        <td>
                            <span class="tank-badge">
                                {{ $tank->tank_number }}
                            </span>
                        </td>

                        <td class="fw-semibold">
                            {{ number_format($tank->capacity_liters) }} L
                        </td>

                        <td>
                            <span class="fw-semibold">
                                {{ number_format($tank->current_stock_liters) }} L
                            </span>
                        </td>

                        <td>
                            <span class="fw-semibold">
                                {{ number_format($tank->minimum_level) }} L
                            </span>
                        </td>

                        <td>

                            @if($tank->status)

                                <span class="status-active">
                                    Active
                                </span>

                            @else

                                <span class="status-inactive">
                                    Inactive
                                </span>

                            @endif

                        </td>

                        <td>

                            <div class="d-flex align-items-center gap-2">

                                <div class="progress flex-grow-1">

                                    <div
                                        class="progress-bar
                                        {{ $percentage < 30 ? 'bg-danger' : ($percentage < 60 ? 'bg-warning' : 'bg-success') }}"
                                        role="progressbar"
                                        style="width: {{ $percentage }}%">
                                    </div>

                                </div>

                                <small class="fw-semibold">
                                    {{ round($percentage) }}%
                                </small>

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="8" class="text-center py-5 border-0">

                            <img src="https://cdn-icons-png.flaticon.com/512/4076/4076549.png"
                                 width="80"
                                 class="mb-3 opacity-50">

                            <div class="fw-semibold text-muted">
                                No fuel tanks found
                            </div>

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection