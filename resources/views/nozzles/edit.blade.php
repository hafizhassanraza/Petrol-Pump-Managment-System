@extends('layouts.app')

@section('content')

<div class="page-card">
    <h3 class="page-title">Edit Nozzle</h3>
    <p class="page-subtitle">Update nozzle details, dispenser, tank, and meter reading.</p>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('nozzles.update', $nozzle->id) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Dispenser</label>
            <select name="dispenser_id" class="form-control">
                @foreach($dispensers as $d)
                    <option value="{{ $d->id }}" {{ $nozzle->dispenser_id == $d->id ? 'selected' : '' }}>
                        {{ $d->dispenser_code }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Tank</label>
            <select name="tank_id" class="form-control">
                @foreach($tanks as $t)
                    <option value="{{ $t->id }}" {{ $nozzle->tank_id == $t->id ? 'selected' : '' }}>
                        {{ $t->tank_number }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Product</label>
            <select name="product_id" class="form-control">
                @foreach($products as $p)
                    <option value="{{ $p->id }}" {{ $nozzle->product_id == $p->id ? 'selected' : '' }}>
                        {{ $p->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Nozzle Number</label>
            <input type="text" name="nozzle_number" class="form-control" value="{{ $nozzle->nozzle_number }}">
        </div>

        <div class="mb-3">
            <label>Meter Reading</label>
            <input type="number" name="current_meter_reading" class="form-control" value="{{ $nozzle->current_meter_reading }}">
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" name="status" class="form-check-input" {{ $nozzle->status ? 'checked' : '' }}>
            <label>Active</label>
        </div>

        <button class="btn btn-primary">Update</button>
        <a href="{{ route('nozzles.index') }}" class="btn btn-secondary">Back</a>
    </form>
</div>

@endsection
