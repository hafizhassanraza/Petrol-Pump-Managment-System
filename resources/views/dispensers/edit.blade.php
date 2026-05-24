@extends('layouts.app')

@section('content')

<h2>Edit Dispenser</h2>

<form method="POST" action="{{ route('dispensers.update', $dispenser->id) }}">
    @csrf
    @method('PUT')

    <div class="mb-3">
        <label>Dispenser Code</label>
        <input type="text" name="dispenser_code"
               value="{{ $dispenser->dispenser_code }}"
               class="form-control">
    </div>

    <div class="mb-3">
        <label>Company</label>
        <input type="text" name="company"
               value="{{ $dispenser->company }}"
               class="form-control">
    </div>

    <div class="mb-3">
        <label>Model</label>
        <input type="text" name="model"
               value="{{ $dispenser->model }}"
               class="form-control">
    </div>

    <div class="form-check mb-3">
        <input type="checkbox"
               name="status"
               class="form-check-input"
               {{ $dispenser->status ? 'checked' : '' }}>
        <label>Active</label>
    </div>

    <button class="btn btn-primary">Update</button>
    <a href="{{ route('dispensers.index') }}" class="btn btn-secondary">Back</a>
</form>

@endsection