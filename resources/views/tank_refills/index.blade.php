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

    .custom-table tbody td{
        padding: 18px 16px;
        vertical-align: middle;
        text-align: center;
        border: 1px solid #d1d5db;
        font-size: 14px;
        color: #6b7280;
    }

    .custom-table tbody td:first-child{
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
    }

    .custom-table tbody td:last-child{
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
    }

    .status-badge{
        background: #dcfce7;
        color: #15803d;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
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
            <h4 >
                Tank Refills
            </h4>
  
        </div>

        <div>
            <a href="{{ route('tank-refills.create') }}" class="btn btn-primary">
                Add Refill
            </a>
        </div>

    </div>


    <div class="table-responsive">

        <table class="table custom-table align-middle">

            <thead class="table-dark">

                <tr>
                    <th>ID</th>
                    <th>Tank</th>
                    <th>Product</th>
                    <th>Invoice</th>
                    <th>Liters</th>
                    <th>Purchase Rate</th>
                    <th>Total Amount</th>
                    <th>Date</th>
                </tr>

            </thead>

            <tbody>

                @forelse($refills as $refill)

                    <tr>
                        <td class="fw-semibold">{{ $refill->id }}</td>
                        <td class="fw-semibold"><span class="tank-badge">{{ $refill->tank->tank_number ?? '-' }}</span></td>
                        <td class="fw-semibold"><span class="product-badge">{{ $refill->product->name ?? '-' }}</span></td>
                        <td class="fw-semibold">{{ $refill->invoice_no }}</td>
                        <td class="fw-semibold">{{ $refill->quantity_liters }}</td>
                        <td class="fw-semibold">{{ $refill->purchase_rate }}</td>
                        <td class="fw-semibold">{{ $refill->total_amount }}</td>
                        <td class="fw-semibold">{{ $refill->received_datetime }}</td>
                    </tr>

                @empty

                    <tr>
                        <td colspan="8" class="text-center">
                            No refill records found.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection