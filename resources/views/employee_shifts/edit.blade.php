@extends('layouts.app')

@section('content')
<div class="page-card">
    <h3 class="page-title">Edit Shift</h3>
    <p class="page-subtitle">
        {{ $shift->employee->name ?? 'Employee' }}
        · Nozzle {{ $shift->nozzle->nozzle_number ?? '—' }}
        · {{ $shift->assigned_date->format('d M Y') }}
        · <span class="badge bg-{{ $shift->status === 'active' ? 'primary' : 'warning text-dark' }}">{{ ucfirst($shift->status) }}</span>
    </p>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if($shift->status === 'submitted' && isset($pricePerLiter) && $pricePerLiter)
        <div class="alert alert-info mb-3">
            <strong>Station selling price:</strong> PKR {{ number_format($pricePerLiter, 2) }} / L
            for cash sales. Agency credit can use a separate sale price.
        </div>
    @elseif($shift->status === 'submitted')
        <div class="alert alert-warning mb-3">No selling price set for this product.</div>
    @endif

    <form method="POST" action="{{ route('employee-shifts.update', $shift->id) }}">
        @csrf
        @method('PUT')

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="border rounded-3 p-3 bg-light h-100">
                    <small class="text-muted d-block">Nozzle</small>
                    <strong>{{ $shift->nozzle->nozzle_number }}</strong>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded-3 p-3 bg-light h-100">
                    <small class="text-muted d-block">Product</small>
                    <strong>{{ $shift->nozzle->product->name ?? '—' }}</strong>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded-3 p-3 bg-light h-100">
                    <small class="text-muted d-block">Opening meter</small>
                    <strong class="text-primary">{{ number_format($shift->opening_reading, 2) }}</strong>
                </div>
            </div>
        </div>

        <div class="border rounded-3 p-3 mb-3">
            <h6 class="fw-semibold mb-3">Employee</h6>
            <select name="employee_id" class="form-control" required>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" @selected(old('employee_id', $shift->employee_id) == $employee->id)>
                        {{ $employee->name }}
                    </option>
                @endforeach
            </select>
        </div>

        @if($shift->status === 'active')
            <div class="border rounded-3 p-3 mb-4">
                <h6 class="fw-semibold mb-3">Opening details</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Opening date *</label>
                        <input type="date" name="assigned_date" class="form-control"
                               value="{{ old('assigned_date', $shift->assigned_date->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Opening meter *</label>
                        <input type="number" step="0.01" name="opening_reading" class="form-control"
                               value="{{ old('opening_reading', $shift->opening_reading) }}" required>
                        <small class="text-muted">Must be ≥ current nozzle meter ({{ number_format($shift->nozzle->current_meter_reading, 2) }}).</small>
                    </div>
                </div>
            </div>
        @else
            <div class="border rounded-3 p-3 mb-3">
                <h6 class="fw-semibold mb-3"><span class="badge bg-danger me-2">1</span> Closing meter & dates</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Opening date *</label>
                        <input type="date" name="assigned_date" class="form-control"
                               value="{{ old('assigned_date', $shift->assigned_date->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Closing date *</label>
                        <input type="date" name="closed_date" class="form-control"
                               value="{{ old('closed_date', optional($shift->closed_date)->format('Y-m-d') ?? $defaultClosingDate) }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Closing meter *</label>
                        <input type="number" step="0.01" name="closing_reading" id="closingReadingInput" class="form-control"
                               value="{{ old('closing_reading', $shift->closing_reading) }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Testing liters</label>
                        <input type="number" step="0.01" name="testing_liters" id="testingLitersInput" class="form-control"
                               value="{{ old('testing_liters', $shift->testing_liters ?? 0) }}">
                    </div>
                </div>
            </div>

            <div class="border rounded-3 p-3 mb-3 bg-light">
                <h6 class="fw-semibold mb-3"><span class="badge bg-secondary me-2">2</span> Liter split</h6>
                <div class="row g-3" id="expectedAmountBox">
                    <div class="col-6 col-md-2"><small class="text-muted d-block">Gross</small><strong id="grossLitersDisplay">—</strong></div>
                    <div class="col-6 col-md-2"><small class="text-muted d-block">Testing</small><strong id="testingLitersDisplay">0.00</strong></div>
                    <div class="col-6 col-md-2"><small class="text-muted d-block">Owner</small><strong id="ownerLitersDisplay">0.00</strong></div>
                    <div class="col-6 col-md-2"><small class="text-muted d-block">Agency</small><strong id="agencyLitersDisplay">0.00</strong></div>
                    <div class="col-6 col-md-2"><small class="text-muted d-block">Cash sales (L)</small><strong id="netLitersDisplay" class="text-primary">—</strong></div>
                    <div class="col-6 col-md-2"><small class="text-muted d-block">Expected cash</small><strong id="expectedAmountDisplay" class="text-success">—</strong></div>
                </div>
            </div>

            <div class="border rounded-3 p-3 mb-3">
                <h6 class="fw-semibold mb-3"><span class="badge bg-secondary me-2">3</span> Owner / agency</h6>
                @include('employee_shifts.partials.owner-agency-fuel')
            </div>

            <div class="border rounded-3 p-3 mb-4">
                <h6 class="fw-semibold mb-3"><span class="badge bg-success me-2">4</span> Money received</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Cash received *</label>
                        <input type="number" step="0.01" name="cash_received" class="form-control"
                               value="{{ old('cash_received', $shift->cash_received) }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Online received *</label>
                        <input type="number" step="0.01" name="online_received" class="form-control"
                               value="{{ old('online_received', $shift->online_received) }}" required>
                    </div>
                </div>
            </div>
        @endif

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('employee-shifts.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Save Changes</button>
        </div>
    </form>
</div>

@if($shift->status === 'submitted')
<script>
(function () {
    const openingReading = {{ (float) $shift->opening_reading }};
    const pricePerLiter = {{ isset($pricePerLiter) && $pricePerLiter ? (float) $pricePerLiter : 0 }};
    const closingInput = document.getElementById('closingReadingInput');
    const testingInput = document.getElementById('testingLitersInput');
    const ownerCheck = document.getElementById('hasOwnerFuel');
    const ownerFields = document.getElementById('ownerFuelFields');
    const ownerLitersInput = document.getElementById('ownerFuelLitersInput');
    const agencyCheck = document.getElementById('hasAgencyFuel');
    const agencyFields = document.getElementById('agencyFuelFields');
    const agencyLitersInput = document.getElementById('agencyFuelLitersInput');
    const agencyPriceInput = document.getElementById('agencySalePriceInput');
    const agencyAmountDisplay = document.getElementById('agencyCreditAmountDisplay');
    const grossDisplay = document.getElementById('grossLitersDisplay');
    const testingDisplay = document.getElementById('testingLitersDisplay');
    const ownerDisplay = document.getElementById('ownerLitersDisplay');
    const agencyDisplay = document.getElementById('agencyLitersDisplay');
    const netDisplay = document.getElementById('netLitersDisplay');
    const amountDisplay = document.getElementById('expectedAmountDisplay');

    function formatNumber(value) {
        return value.toLocaleString('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function ownerLitersValue() { return ownerCheck.checked ? (parseFloat(ownerLitersInput.value) || 0) : 0; }
    function agencyLitersValue() { return agencyCheck.checked ? (parseFloat(agencyLitersInput.value) || 0) : 0; }
    function agencyPriceValue() {
        if (!agencyCheck.checked) return 0;
        const entered = parseFloat(agencyPriceInput.value);
        if (!Number.isNaN(entered) && entered > 0) return entered;
        return pricePerLiter > 0 ? pricePerLiter : 0;
    }
    function updateAgencyCreditPreview() {
        if (!agencyCheck.checked) { agencyAmountDisplay.textContent = '—'; return; }
        const liters = agencyLitersValue();
        const price = agencyPriceValue();
        agencyAmountDisplay.textContent = (liters > 0 && price > 0) ? ('PKR ' + formatNumber(liters * price)) : '—';
    }
    function toggleOwnerFuel() {
        ownerFields.style.display = ownerCheck.checked ? 'block' : 'none';
        if (!ownerCheck.checked) ownerLitersInput.value = '';
        updateExpectedAmount();
    }
    function toggleAgencyFuel() {
        agencyFields.style.display = agencyCheck.checked ? 'block' : 'none';
        if (agencyCheck.checked && !agencyPriceInput.value && pricePerLiter > 0) {
            agencyPriceInput.value = pricePerLiter.toFixed(2);
        }
        if (!agencyCheck.checked) agencyLitersInput.value = '';
        updateExpectedAmount();
    }
    function updateExpectedAmount() {
        const closing = parseFloat(closingInput.value);
        const testing = parseFloat(testingInput.value) || 0;
        const ownerLiters = ownerLitersValue();
        const agencyLiters = agencyLitersValue();
        testingDisplay.textContent = formatNumber(testing);
        ownerDisplay.textContent = formatNumber(ownerLiters);
        agencyDisplay.textContent = formatNumber(agencyLiters);
        updateAgencyCreditPreview();
        if (Number.isNaN(closing)) {
            grossDisplay.textContent = '—'; netDisplay.textContent = '—'; amountDisplay.textContent = '—'; return;
        }
        if (closing < openingReading) {
            grossDisplay.textContent = formatNumber(closing - openingReading);
            netDisplay.textContent = 'Invalid'; amountDisplay.textContent = 'Closing < opening'; amountDisplay.className = 'text-danger'; return;
        }
        const gross = closing - openingReading;
        const net = gross - testing - ownerLiters - agencyLiters;
        grossDisplay.textContent = formatNumber(gross);
        if (net < 0) {
            netDisplay.textContent = 'Invalid'; amountDisplay.textContent = 'Split exceeds gross'; amountDisplay.className = 'text-danger'; return;
        }
        netDisplay.textContent = formatNumber(net);
        amountDisplay.className = 'text-success';
        if (pricePerLiter <= 0 && net > 0) { amountDisplay.textContent = 'No price'; amountDisplay.className = 'text-danger'; return; }
        amountDisplay.textContent = 'PKR ' + formatNumber(net * pricePerLiter);
    }
    ownerCheck.addEventListener('change', toggleOwnerFuel);
    agencyCheck.addEventListener('change', toggleAgencyFuel);
    closingInput.addEventListener('input', updateExpectedAmount);
    testingInput.addEventListener('input', updateExpectedAmount);
    ownerLitersInput.addEventListener('input', updateExpectedAmount);
    agencyLitersInput.addEventListener('input', updateExpectedAmount);
    agencyPriceInput.addEventListener('input', updateExpectedAmount);
    updateExpectedAmount();
})();
</script>
@endif
@endsection
