@extends('layouts.app')

@section('content')

<div class="page-card">
    <h3 class="page-title">Create Nozzle</h3>
    <p class="page-subtitle">Add a new nozzle and associate it with a dispenser and tank.</p>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('nozzles.store') }}">
    @csrf

    <div class="mb-3">
        <label>Dispenser</label>
        <select name="dispenser_id" class="form-control">
            @foreach($dispensers as $d)
                <option value="{{ $d->id }}">{{ $d->dispenser_code }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label>Tank</label>
        <select name="tank_id" class="form-control">
            @foreach($tanks as $t)
                <option value="{{ $t->id }}">{{ $t->tank_number }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label>Product</label>
        <select name="product_id" class="form-control">
            @foreach($products as $p)
                <option value="{{ $p->id }}">{{ $p->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label>Nozzle Number</label>
        <input type="text" name="nozzle_number" class="form-control">
    </div>

    <div class="mb-3">
        <label>Meter Reading</label>
        <input type="number" name="current_meter_reading" class="form-control">
    </div>

    <div class="form-check mb-3">
        <input type="checkbox" name="status" checked class="form-check-input">
        <label>Active</label>
    </div>

    <button class="btn btn-success">Save</button>
</form>

</div>

@endsection