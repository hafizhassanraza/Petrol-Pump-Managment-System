@extends('layouts.app')

@section('content')
<div class="page-card">
    <h3 class="page-title">Open / Assign Shift</h3>
    <p class="page-subtitle">Start a new employee shift on a nozzle. Closing readings and cash are entered later when you close the shift.</p>

    <div class="alert alert-light border mb-4">
        <div class="row g-2">
            <div class="col-md-4">
                <small class="text-muted d-block">Business day</small>
                <strong>{{ $businessDate->format('d M Y') }}</strong>
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">Shift window</small>
                <strong>{{ $shift->name ?? '9 AM – 9 AM' }}</strong>
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">How it works</small>
                <span class="text-muted">Open now → sell during day → Close shift later</span>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('employee-shifts.store') }}">
        @csrf

        <div class="border rounded-3 p-3 mb-3">
            <h6 class="fw-semibold mb-3"><span class="badge bg-primary me-2">1</span> Who is working?</h6>
            <div class="mb-0">
                <label class="form-label">Employee *</label>
                <select name="employee_id" class="form-control" required>
                    <option value="">Select employee</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>
                            {{ $employee->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="border rounded-3 p-3 mb-3">
            <h6 class="fw-semibold mb-3"><span class="badge bg-primary me-2">2</span> Which nozzle?</h6>
            <div class="mb-0">
                <label class="form-label">Nozzle *</label>
                <select name="nozzle_id" id="nozzle_id" class="form-control" required>
                    <option value="" data-meter="0" data-product="" data-stock="">Select nozzle</option>
                    @foreach($nozzles as $nozzle)
                        <option value="{{ $nozzle->id }}"
                                data-meter="{{ $nozzle->current_meter_reading }}"
                                data-product="{{ $nozzle->product->name ?? '' }}"
                                data-stock="{{ number_format($nozzle->tank->current_stock_liters ?? 0, 2) }}"
                                @selected(old('nozzle_id') == $nozzle->id)>
                            {{ $nozzle->nozzle_number }}
                            — {{ $nozzle->product->name ?? 'Fuel' }}
                            — {{ $nozzle->dispenser->dispenser_code ?? 'Dispenser' }}
                            (meter {{ number_format($nozzle->current_meter_reading, 2) }})
                        </option>
                    @endforeach
                </select>
                <div id="nozzleHint" class="small text-muted mt-2" style="display:none;">
                    Product: <strong id="hintProduct">—</strong>
                    · Tank stock: <strong id="hintStock">—</strong> L
                    · Current meter: <strong id="hintMeter">—</strong>
                </div>
            </div>
        </div>

        <div class="border rounded-3 p-3 mb-4">
            <h6 class="fw-semibold mb-3"><span class="badge bg-primary me-2">3</span> Opening details</h6>
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label class="form-label">Opening date *</label>
                    <input type="date"
                           name="assigned_date"
                           class="form-control"
                           value="{{ old('assigned_date', $defaultOpeningDate) }}"
                           required>
                    <small class="text-muted">Usually today’s business date. Used as the shift opening date.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Opening meter reading *</label>
                    <input type="number"
                           step="0.01"
                           name="opening_reading"
                           id="opening_reading"
                           class="form-control"
                           value="{{ old('opening_reading') }}"
                           required>
                    <small class="text-muted">Filled from nozzle meter when you pick a nozzle. Can be higher, never lower.</small>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-success">
            <i class="bi bi-play-circle"></i> Open Shift
        </button>
        <a href="{{ route('employee-shifts.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>

@push('scripts')
<script>
(function () {
    const select = document.getElementById('nozzle_id');
    const opening = document.getElementById('opening_reading');
    const hint = document.getElementById('nozzleHint');

    function syncNozzle() {
        const opt = select.selectedOptions[0];
        if (!opt || !opt.value) {
            hint.style.display = 'none';
            return;
        }
        const meter = opt.dataset.meter || '0';
        if (!opening.value) {
            opening.value = meter;
        } else if (!document.activeElement || document.activeElement !== opening) {
            opening.value = meter;
        }
        document.getElementById('hintProduct').textContent = opt.dataset.product || '—';
        document.getElementById('hintStock').textContent = opt.dataset.stock || '0.00';
        document.getElementById('hintMeter').textContent = Number(meter).toLocaleString('en-PK', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
        hint.style.display = 'block';
    }

    select.addEventListener('change', function () {
        const meter = this.selectedOptions[0]?.dataset.meter;
        if (meter) opening.value = meter;
        syncNozzle();
    });
    syncNozzle();
})();
</script>
@endpush
@endsection
