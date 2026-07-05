@extends('layouts.app')

@section('content')

<div class="page-card">
    <h3 class="page-title">Edit Shift — {{ $shift->employee->name ?? 'Employee' }}</h3>
    <p class="page-subtitle">
        Nozzle {{ $shift->nozzle->nozzle_number ?? '—' }} · {{ $shift->assigned_date }}
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
            <strong>Recalculation price:</strong> PKR {{ number_format($pricePerLiter, 2) }} / L
            — Amount = Net Liters × current selling price
        </div>
    @elseif($shift->status === 'submitted')
        <div class="alert alert-warning mb-3">
            No selling price set for this product. Add a price before saving changes.
        </div>
    @endif

    <form method="POST" action="{{ route('employee-shifts.update', $shift->id) }}">
        @csrf
        @method('PUT')

        <div class="row mb-3 p-3 rounded-3 border bg-light">
            <div class="col-md-4">
                <small class="text-muted">Nozzle</small>
                <div class="fw-semibold">{{ $shift->nozzle->nozzle_number }}</div>
            </div>
            <div class="col-md-4">
                <small class="text-muted">Product</small>
                <div class="fw-semibold">{{ $shift->nozzle->product->name ?? '—' }}</div>
            </div>
            <div class="col-md-4">
                <small class="text-muted">Opening Reading</small>
                <div class="fw-bold text-primary">{{ number_format($shift->opening_reading, 2) }}</div>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Employee</label>
            <select name="employee_id" class="form-control" required>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" @selected(old('employee_id', $shift->employee_id) == $employee->id)>
                        {{ $employee->name }}
                    </option>
                @endforeach
            </select>
        </div>

        @if($shift->status === 'active')
            <div class="mb-3">
                <label class="form-label fw-semibold">Opening Reading</label>
                <input type="number" step="0.01" name="opening_reading" class="form-control"
                       value="{{ old('opening_reading', $shift->opening_reading) }}" required>
                <small class="text-muted">Must be ≥ current nozzle meter ({{ number_format($shift->nozzle->current_meter_reading, 2) }}).</small>
            </div>
        @else
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Closing Reading</label>
                    <input type="number" step="0.01" name="closing_reading" id="closingReadingInput" class="form-control"
                           value="{{ old('closing_reading', $shift->closing_reading) }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Testing Liters</label>
                    <input type="number" step="0.01" name="testing_liters" id="testingLitersInput" class="form-control"
                           value="{{ old('testing_liters', $shift->testing_liters ?? 0) }}">
                </div>
            </div>

            <div class="col-12 mb-3">
                <div id="expectedAmountBox" class="p-3 rounded-3 border bg-light">
                    <h6 class="fw-semibold mb-3"><i class="bi bi-calculator"></i> Recalculated Expected Amount</h6>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <small class="text-muted d-block">Gross Liters</small>
                            <strong id="grossLitersDisplay">—</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Testing Liters</small>
                            <strong id="testingLitersDisplay">0.00</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Net Liters Sold</small>
                            <strong id="netLitersDisplay" class="text-primary">—</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Expected Amount (PKR)</small>
                            <strong id="expectedAmountDisplay" class="fs-5 text-success">—</strong>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2">
                        Net liters × selling price (PKR {{ isset($pricePerLiter) && $pricePerLiter ? number_format($pricePerLiter, 2) : '0.00' }} / L)
                    </small>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Cash Received</label>
                    <input type="number" step="0.01" name="cash_received" class="form-control"
                           value="{{ old('cash_received', $shift->cash_received) }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Online Received</label>
                    <input type="number" step="0.01" name="online_received" class="form-control"
                           value="{{ old('online_received', $shift->online_received) }}" required>
                </div>
            </div>
        @endif

        <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="{{ route('employee-shifts.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle"></i> Save Changes
            </button>
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
    const grossDisplay = document.getElementById('grossLitersDisplay');
    const testingDisplay = document.getElementById('testingLitersDisplay');
    const netDisplay = document.getElementById('netLitersDisplay');
    const amountDisplay = document.getElementById('expectedAmountDisplay');
    const amountBox = document.getElementById('expectedAmountBox');

    function formatNumber(value) {
        return value.toLocaleString('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function updateExpectedAmount() {
        const closing = parseFloat(closingInput.value);
        const testing = parseFloat(testingInput.value) || 0;
        testingDisplay.textContent = formatNumber(testing);

        if (Number.isNaN(closing)) {
            grossDisplay.textContent = '—';
            netDisplay.textContent = '—';
            amountDisplay.textContent = '—';
            return;
        }

        if (closing < openingReading) {
            grossDisplay.textContent = formatNumber(closing - openingReading);
            netDisplay.textContent = 'Invalid';
            amountDisplay.textContent = 'Closing must be ≥ opening';
            amountDisplay.className = 'fs-6 text-danger';
            amountBox.classList.add('border-danger');
            return;
        }

        amountBox.classList.remove('border-danger');
        const gross = closing - openingReading;
        const net = gross - testing;
        grossDisplay.textContent = formatNumber(gross);

        if (testing > gross) {
            netDisplay.textContent = 'Invalid';
            amountDisplay.textContent = 'Testing exceeds gross';
            amountDisplay.className = 'fs-6 text-danger';
            return;
        }

        if (net <= 0) {
            netDisplay.textContent = formatNumber(Math.max(net, 0));
            amountDisplay.textContent = net === 0 ? '0.00' : 'Net liters must be > 0';
            amountDisplay.className = net === 0 ? 'fs-5 text-muted' : 'fs-6 text-danger';
            return;
        }

        netDisplay.textContent = formatNumber(net);

        if (pricePerLiter <= 0) {
            amountDisplay.textContent = 'No price set';
            amountDisplay.className = 'fs-6 text-danger';
            return;
        }

        amountDisplay.textContent = formatNumber(net * pricePerLiter);
        amountDisplay.className = 'fs-5 text-success';
    }

    closingInput.addEventListener('input', updateExpectedAmount);
    testingInput.addEventListener('input', updateExpectedAmount);
    updateExpectedAmount();
})();
</script>
@endif

@endsection
