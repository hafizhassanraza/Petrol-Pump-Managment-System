@extends('layouts.app')

@section('content')
<div class="page-card">
    <h3 class="page-title">Close Shift</h3>
    <p class="page-subtitle">
        {{ $shift->employee->name ?? 'Employee' }}
        · Nozzle {{ $shift->nozzle->nozzle_number ?? '—' }}
        · {{ $shift->nozzle->product->name ?? 'Fuel' }}
        · Opened {{ $shift->assigned_date?->format('d M Y') }}
    </p>

    @if(isset($pricePerLiter) && $pricePerLiter)
        <div class="alert alert-info mb-3">
            <strong>Station selling price:</strong>
            PKR {{ number_format($pricePerLiter, 2) }} / L
            — used for cash/online sales amount.
            Agency credit can use its own sale price.
        </div>
    @else
        <div class="alert alert-warning mb-3">
            No selling price set for this product.
            <a href="{{ route('product-prices.create') }}">Add price</a> before closing.
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Please fix:</strong>
            <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="border rounded-3 p-3 h-100 bg-light">
                <small class="text-muted d-block">Employee</small>
                <strong>{{ $shift->employee->name }}</strong>
            </div>
        </div>
        <div class="col-md-3">
            <div class="border rounded-3 p-3 h-100 bg-light">
                <small class="text-muted d-block">Nozzle / Product</small>
                <strong>{{ $shift->nozzle->nozzle_number }} · {{ $shift->nozzle->product->name }}</strong>
            </div>
        </div>
        <div class="col-md-3">
            <div class="border rounded-3 p-3 h-100 bg-light">
                <small class="text-muted d-block">Opening meter</small>
                <strong class="text-primary fs-5">{{ number_format($shift->opening_reading, 2) }}</strong>
            </div>
        </div>
        <div class="col-md-3">
            <div class="border rounded-3 p-3 h-100 bg-light">
                <small class="text-muted d-block">Opening date</small>
                <strong>{{ $shift->assigned_date?->format('d M Y') }}</strong>
            </div>
        </div>
    </div>

    <form method="POST">
        @csrf

        <div class="border rounded-3 p-3 mb-3">
            <h6 class="fw-semibold mb-3"><span class="badge bg-danger me-2">1</span> Closing meter & date</h6>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Closing date *</label>
                    <input type="date"
                           name="closed_date"
                           class="form-control @error('closed_date') is-invalid @enderror"
                           value="{{ old('closed_date', $defaultClosingDate) }}"
                           required>
                    <small class="text-muted">Cannot be before opening date ({{ $shift->assigned_date->format('d M Y') }}). Reports use this date.</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Closing meter reading *</label>
                    <input type="number"
                           step="0.01"
                           name="closing_reading"
                           id="closingReadingInput"
                           class="form-control @error('closing_reading') is-invalid @enderror"
                           value="{{ old('closing_reading') }}"
                           placeholder="Must be ≥ {{ number_format($shift->opening_reading, 2) }}"
                           required>
                    @error('closing_reading')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6 mb-0">
                    <label class="form-label fw-semibold">Testing liters</label>
                    <input type="number"
                           step="0.01"
                           name="testing_liters"
                           id="testingLitersInput"
                           class="form-control @error('testing_liters') is-invalid @enderror"
                           value="{{ old('testing_liters', 0) }}"
                           placeholder="0.00">
                    <small class="text-muted">Fuel pumped for testing (not sold). Stays in tank conceptually.</small>
                </div>
            </div>
        </div>

        <div class="border rounded-3 p-3 mb-3 bg-light">
            <h6 class="fw-semibold mb-3"><span class="badge bg-secondary me-2">2</span> How liters are split</h6>
            <div class="row g-3" id="expectedAmountBox">
                <div class="col-6 col-md-2">
                    <small class="text-muted d-block">Gross</small>
                    <strong id="grossLitersDisplay">—</strong>
                    <div class="small text-muted">Close − Open</div>
                </div>
                <div class="col-6 col-md-2">
                    <small class="text-muted d-block">Testing</small>
                    <strong id="testingLitersDisplay">0.00</strong>
                </div>
                <div class="col-6 col-md-2">
                    <small class="text-muted d-block">Owner</small>
                    <strong id="ownerLitersDisplay">0.00</strong>
                </div>
                <div class="col-6 col-md-2">
                    <small class="text-muted d-block">Agency</small>
                    <strong id="agencyLitersDisplay">0.00</strong>
                </div>
                <div class="col-6 col-md-2">
                    <small class="text-muted d-block">Cash sales (L)</small>
                    <strong id="netLitersDisplay" class="text-primary">—</strong>
                </div>
                <div class="col-6 col-md-2">
                    <small class="text-muted d-block">Expected cash sales</small>
                    <strong id="expectedAmountDisplay" class="text-success">—</strong>
                </div>
            </div>
            <small class="text-muted d-block mt-3">
                Cash sales liters = Gross − Testing − Owner − Agency.
                Expected amount = cash sales liters × station price
                (PKR {{ isset($pricePerLiter) && $pricePerLiter ? number_format($pricePerLiter, 2) : '0.00' }} / L).
            </small>
        </div>

        <div class="border rounded-3 p-3 mb-3">
            <h6 class="fw-semibold mb-3"><span class="badge bg-secondary me-2">3</span> Optional: owner / agency</h6>
            @include('employee_shifts.partials.owner-agency-fuel')
        </div>

        <div class="border rounded-3 p-3 mb-4">
            <h6 class="fw-semibold mb-3"><span class="badge bg-success me-2">4</span> Money received (cash sales only)</h6>
            <p class="small text-muted">Compare against expected cash sales amount above. Agency credit is paid later separately.</p>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Cash received *</label>
                    <div class="input-group">
                        <span class="input-group-text">Rs</span>
                        <input type="number"
                               step="0.01"
                               name="cash_received"
                               class="form-control @error('cash_received') is-invalid @enderror"
                               value="{{ old('cash_received') }}"
                               placeholder="0.00"
                               required>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Online / bank received *</label>
                    <div class="input-group">
                        <span class="input-group-text">Rs</span>
                        <input type="number"
                               step="0.01"
                               name="online_received"
                               class="form-control @error('online_received') is-invalid @enderror"
                               value="{{ old('online_received') }}"
                               placeholder="0.00"
                               required>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('employee-shifts.index') }}" class="btn btn-light border">Cancel</a>
            <button type="submit" class="btn btn-success px-4">
                <i class="bi bi-check-circle"></i> Submit & Close Shift
            </button>
        </div>
    </form>
</div>

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
    const amountBox = document.getElementById('expectedAmountBox');

    function formatNumber(value) {
        return value.toLocaleString('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function ownerLitersValue() {
        return ownerCheck.checked ? (parseFloat(ownerLitersInput.value) || 0) : 0;
    }

    function agencyLitersValue() {
        return agencyCheck.checked ? (parseFloat(agencyLitersInput.value) || 0) : 0;
    }

    function agencyPriceValue() {
        if (!agencyCheck.checked) return 0;
        const entered = parseFloat(agencyPriceInput.value);
        if (!Number.isNaN(entered) && entered > 0) return entered;
        return pricePerLiter > 0 ? pricePerLiter : 0;
    }

    function updateAgencyCreditPreview() {
        if (!agencyCheck.checked) {
            agencyAmountDisplay.textContent = '—';
            return;
        }
        const liters = agencyLitersValue();
        const price = agencyPriceValue();
        if (liters <= 0 || price <= 0) {
            agencyAmountDisplay.textContent = '—';
            return;
        }
        agencyAmountDisplay.textContent = 'PKR ' + formatNumber(liters * price);
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
        if (!agencyCheck.checked) {
            agencyLitersInput.value = '';
        }
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
            grossDisplay.textContent = '—';
            netDisplay.textContent = '—';
            amountDisplay.textContent = '—';
            return;
        }

        if (closing < openingReading) {
            grossDisplay.textContent = formatNumber(closing - openingReading);
            netDisplay.textContent = 'Invalid';
            amountDisplay.textContent = 'Closing < opening';
            amountDisplay.className = 'text-danger';
            return;
        }

        const gross = closing - openingReading;
        const net = gross - testing - ownerLiters - agencyLiters;
        grossDisplay.textContent = formatNumber(gross);

        if (net < 0) {
            netDisplay.textContent = 'Invalid';
            amountDisplay.textContent = 'Split exceeds gross';
            amountDisplay.className = 'text-danger';
            return;
        }

        netDisplay.textContent = formatNumber(net);
        amountDisplay.className = 'text-success';

        if (net === 0) {
            amountDisplay.textContent = 'PKR ' + formatNumber(0);
            return;
        }

        if (pricePerLiter <= 0) {
            amountDisplay.textContent = 'No price';
            amountDisplay.className = 'text-danger';
            return;
        }

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
@endsection
