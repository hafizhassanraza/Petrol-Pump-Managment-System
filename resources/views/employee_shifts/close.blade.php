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
    .custom-gray{
        background-color: lightyellow;
    }
</style>

<div class="container-fluid">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center ">

        <div>
            <h4>
                Shift Closing - {{ $shift->employee->name }} (Nozzle {{ $shift->nozzle->nozzle_number }})
            </h4>
        </div>

    </div>


    {{-- Alerts --}}
    @if(session('error'))

        <div class="alert alert-danger alert-dismissible fade show" role="alert">

            <i class="bi bi-exclamation-triangle-fill me-2"></i>

            {{ session('error') }}

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
            </button>

        </div>

    @endif


    {{-- Validation Errors --}}
    @if($errors->any())

        <div class="alert alert-danger">

            <strong>Please fix the following errors:</strong>

            <ul class="mb-0 mt-2">

                @foreach($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    @endif



        {{-- Close Shift Form --}}
        <div >

            <div class="card shadow-sm border-0">


                <div class="card-header bg-primary text-white">

                                <h5 class="mb-0">
                                    <i class="bi bi-info-circle"></i>
                                    Shift Closing Information
                                </h5>

                            </div>

                <div class="card-body">




                    {{-- Shift Information --}}
                  <div class="mb-4 p-3 rounded-4">

                    <div class="card shadow-sm border-0 h-100 custom-gray">

                        <div class="card-body">

                            {{-- First Row --}}
                            <div class="row">

                                {{-- Employee --}}
                                <div class="col-md-4 ">

                                    <small class="text-muted d-block">
                                        Employee
                                    </small>

                                    <h6 class="fw-semibold mb-0">
                                        {{ $shift->employee->name }}
                                    </h6>

                                </div>


                                {{-- Nozzle Number --}}
                                <div class="col-md-4 ">

                                    <small class="text-muted d-block">
                                        Nozzle Number
                                    </small>

                                    <h6 class="fw-semibold mb-0">
                                        {{ $shift->nozzle->nozzle_number }}
                                    </h6>

                                </div>


                                {{-- Product --}}
                                <div class="col-md-4 ">

                                    <small class="text-muted d-block">
                                        Product
                                    </small>

                                    <span class="badge bg-success fs-6 px-3 py-2">
                                        {{ $shift->nozzle->product->name }}
                                    </span>

                                </div>

                            </div>


                            {{-- Second Row --}}
                            <div class="row">

                                <div class="col-md-12">

                                    <small class="text-muted d-block">
                                        Opening Reading
                                    </small>

                                    <h4 class="fw-bold text-primary mb-0">
                                        {{ number_format($shift->opening_reading, 2) }}
                                    </h4>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                    <form method="POST">

                        @csrf


                        <div class="row">

                            {{-- Closing Reading --}}
                            <div class="col-md-6 mb-3">

                                <label class="form-label fw-semibold">

                                    Closing Reading

                                </label>

                                <div class="input-group">

                                    <span class="input-group-text">
                                        <i class="bi bi-speedometer2"></i>
                                    </span>

                                    <input type="number"
                                           step="0.01"
                                           name="closing_reading"
                                           class="form-control @error('closing_reading') is-invalid @enderror"
                                           value="{{ old('closing_reading') }}"
                                           placeholder="Enter closing reading"
                                           required>

                                </div>

                                @error('closing_reading')

                                    <div class="invalid-feedback d-block">

                                        {{ $message }}

                                    </div>

                                @enderror

                            </div>



                            {{-- Testing Liters --}}
                            <div class="col-md-6 mb-3">

                                <label class="form-label fw-semibold">

                                    Testing Liters

                                </label>

                                <div class="input-group">

                                    <span class="input-group-text">
                                        <i class="bi bi-droplet"></i>
                                    </span>

                                    <input type="number"
                                           step="0.01"
                                           name="testing_liters"
                                           class="form-control @error('testing_liters') is-invalid @enderror"
                                           value="{{ old('testing_liters', 0) }}"
                                           placeholder="0.00">

                                </div>

                                @error('testing_liters')

                                    <div class="invalid-feedback d-block">

                                        {{ $message }}

                                    </div>

                                @enderror

                            </div>



                            {{-- Cash Received --}}
                            <div class="col-md-6 mb-3">

                                <label class="form-label fw-semibold">

                                    Cash Received

                                </label>

                                <div class="input-group">

                                    <span class="input-group-text">
                                        Rs
                                    </span>

                                    <input type="number"
                                           step="0.01"
                                           name="cash_received"
                                           class="form-control @error('cash_received') is-invalid @enderror"
                                           value="{{ old('cash_received') }}"
                                           placeholder="Enter cash amount"
                                           required>

                                </div>

                                @error('cash_received')

                                    <div class="invalid-feedback d-block">

                                        {{ $message }}

                                    </div>

                                @enderror

                            </div>



                            {{-- Online Received --}}
                            <div class="col-md-6 mb-3">

                                <label class="form-label fw-semibold">

                                    Online Received

                                </label>

                                <div class="input-group">

                                    <span class="input-group-text">
                                        Rs
                                    </span>

                                    <input type="number"
                                           step="0.01"
                                           name="online_received"
                                           class="form-control @error('online_received') is-invalid @enderror"
                                           value="{{ old('online_received') }}"
                                           placeholder="Enter online amount"
                                           required>

                                </div>

                                @error('online_received')

                                    <div class="invalid-feedback d-block">

                                        {{ $message }}

                                    </div>

                                @enderror

                            </div>

                        </div>


                        {{-- Buttons --}}
                        <div class="d-flex justify-content-end gap-2 mt-4">

                            <a href="{{ route('employee-shifts.index') }}"
                               class="btn btn-light border">

                                Cancel

                            </a>

                            <button type="submit"
                                    class="btn btn-success px-4">

                                <i class="bi bi-check-circle"></i>

                                Submit Shift

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>


</div>

@endsection