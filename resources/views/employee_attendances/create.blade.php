@extends('layouts.app')

@section('content')
<div class="page-card">
    <h3 class="page-title">Mark Attendance</h3>
    <p class="page-subtitle">Record daily attendance for a station employee.</p>

    <form method="POST" action="{{ route('employee-attendances.store') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">Employee *</label>
            <select name="employee_id" class="form-control" required>
                <option value="">Select employee</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>
                        {{ $employee->name }} ({{ $employee->employee_code }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Date *</label>
            <input type="date" name="attendance_date" class="form-control" value="{{ old('attendance_date', $defaultDate) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Status *</label>
            <select name="status" id="status" class="form-control" required>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(old('status', 'present') === $status)>
                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div id="timeFields" class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Check In</label>
                <input type="time" name="check_in" id="check_in" class="form-control" value="{{ old('check_in') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Check Out</label>
                <input type="time" name="check_out" id="check_out" class="form-control" value="{{ old('check_out') }}">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
        </div>

        <button type="submit" class="btn btn-success">Save Attendance</button>
        <a href="{{ route('employee-attendances.index') }}" class="btn btn-secondary">Back</a>
    </form>
</div>

@push('scripts')
<script>
(function () {
    const status = document.getElementById('status');
    const timeFields = document.getElementById('timeFields');
    function toggleTimes() {
        const hide = ['absent', 'on_leave'].includes(status.value);
        timeFields.style.display = hide ? 'none' : 'flex';
        if (hide) {
            document.getElementById('check_in').value = '';
            document.getElementById('check_out').value = '';
        }
    }
    status.addEventListener('change', toggleTimes);
    toggleTimes();
})();
</script>
@endpush
@endsection
