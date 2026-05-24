@extends('layouts.app')

@section('content')

<h2>Create Dispenser</h2>

<form method="POST" action="{{ route('dispensers.store') }}">
    @csrf

    <div class="mb-3">
        <label>Dispenser Code</label>
        <input type="text" name="dispenser_code" class="form-control">
    </div>

    <div class="mb-3">
        <label>Company</label>
        <input type="text" name="company" class="form-control">
    </div>

    <div class="mb-3">
        <label>Model</label>
        <input type="text" name="model" class="form-control">
    </div>

    <div class="form-check mb-3">
        <input type="checkbox" name="status" class="form-check-input" checked>
        <label>Active</label>
    </div>

    <button class="btn btn-success">Save</button>
    <a href="{{ route('dispensers.index') }}" class="btn btn-secondary">Back</a>
</form>

@endsection