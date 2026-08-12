@extends('layouts.app')

@section('content')

<div class="page-card">
    <h3 class="page-title">Add Employee Salary</h3>
    <p class="page-subtitle">Record full salary, advance, partial payment, or bonus.</p>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('employee-salaries.store') }}" id="salaryForm">
        @csrf

        <div class="row">
            <div class="col-md-6 mb-3">
                <label>Employee *</label>
                <select name="employee_id" id="employeeId" class="form-control" required>
                    <option value="">Select employee</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}"
                                @selected((string) old('employee_id', $selectedEmployeeId) === (string) $employee->id)>
                            {{ $employee->name }} ({{ $employee->employee_code }}) — PKR {{ money($employee->salary) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6 mb-3">
                <label>Payment Type *</label>
                <select name="type" id="salaryType" class="form-control" required>
                    @foreach($typeLabels as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $defaultType) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label>Amount (PKR) *</label>
                <input type="number" step="0.01" min="0.01" name="amount" id="salaryAmount"
                       class="form-control" value="{{ old('amount') }}" required>
                <small class="text-muted" id="rateHint">Select employee to see monthly rate.</small>
            </div>

            <div class="col-md-4 mb-3">
                <label>Payment Date *</label>
                <input type="date" name="payment_date" class="form-control"
                       value="{{ old('payment_date', $defaultDate) }}" required>
            </div>

            <div class="col-md-4 mb-3">
                <label>Salary Month *</label>
                <input type="month" name="salary_month" class="form-control"
                       value="{{ old('salary_month', $defaultMonth) }}" required>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label>Payment Method *</label>
                <select name="payment_method" class="form-control" required>
                    @foreach($paymentMethods as $method)
                        <option value="{{ $method }}" @selected(old('payment_method', 'cash') === $method)>
                            {{ ucfirst($method) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label>Reference No</label>
                <input type="text" name="reference_no" class="form-control" value="{{ old('reference_no') }}">
            </div>
            <div class="col-md-4 mb-3">
                <label>Notes</label>
                <input type="text" name="notes" class="form-control" value="{{ old('notes') }}">
            </div>
        </div>

        <button class="btn btn-success">Save Salary Record</button>
        <a href="{{ route('employee-salaries.index') }}" class="btn btn-secondary">Back</a>
    </form>
</div>

<script>
(function () {
    const rates = @json($employeeRates);
    const employeeSelect = document.getElementById('employeeId');
    const typeSelect = document.getElementById('salaryType');
    const amountInput = document.getElementById('salaryAmount');
    const rateHint = document.getElementById('rateHint');

    function syncAmount() {
        const rate = Number(rates[employeeSelect.value] || 0);
        rateHint.textContent = rate > 0
            ? ('Monthly rate: PKR ' + rate.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }))
            : 'Select employee to see monthly rate.';

        if (!amountInput.value && typeSelect.value === 'full' && rate > 0) {
            amountInput.value = rate.toFixed(2);
        }
    }

    employeeSelect.addEventListener('change', function () {
        if (typeSelect.value === 'full') {
            const rate = Number(rates[employeeSelect.value] || 0);
            if (rate > 0) amountInput.value = rate.toFixed(2);
        }
        syncAmount();
    });
    typeSelect.addEventListener('change', function () {
        if (typeSelect.value === 'full') {
            const rate = Number(rates[employeeSelect.value] || 0);
            if (rate > 0) amountInput.value = rate.toFixed(2);
        }
        syncAmount();
    });
    syncAmount();
})();
</script>

@endsection
