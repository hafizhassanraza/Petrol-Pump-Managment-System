@extends('layouts.app')

@section('content')

<div class="page-card">
    <div class="alert alert-info mb-3">
        <strong>Business day:</strong> {{ $businessDate->format('d M Y') }}
        &nbsp;|&nbsp;
        <strong>Shift:</strong> {{ $shift->name ?? '9 AM – 9 AM' }}
        <small class="d-block mt-1 text-muted">Station day runs 9:00 AM to next day 9:00 AM.</small>
    </div>

    <form method="POST" action="{{ route('employee-shifts.store') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">Employee *</label>
            <select name="employee_id" class="form-control" required>
                <option value="">Select employee</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Nozzle *</label>
            <select name="nozzle_id" id="nozzle_id" class="form-control" required>
                <option value="" data-meter="0">Select nozzle</option>
                @foreach($nozzles as $nozzle)
                    <option value="{{ $nozzle->id }}" data-meter="{{ $nozzle->current_meter_reading }}">
                        {{ $nozzle->nozzle_number }}
                        — {{ $nozzle->dispenser->dispenser_code ?? '' }}
                        — {{ $nozzle->product->name ?? '' }}
                        (meter: {{ number_format($nozzle->current_meter_reading, 2) }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Opening meter reading *</label>
            <input type="number" step="0.01" name="opening_reading" id="opening_reading" class="form-control" required>
            <small class="text-muted">Must match or exceed current nozzle meter.</small>
        </div>

        <button type="submit" class="btn btn-success">Assign Shift</button>
        <a href="{{ route('employee-shifts.index') }}" class="btn btn-secondary">Back</a>
    </form>
</div>

@push('scripts')
<script>
document.getElementById('nozzle_id').addEventListener('change', function () {
    const meter = this.selectedOptions[0]?.dataset.meter;
    if (meter) document.getElementById('opening_reading').value = meter;
});
</script>
@endpush
@endsection
