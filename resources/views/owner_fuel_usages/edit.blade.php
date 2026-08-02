@extends('layouts.app')

@section('content')

<div class="page-card">
    <h3 class="page-title">Edit Owner Fuel Usage</h3>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($pricePerLiter)
        <div class="alert alert-info mb-3">
            <strong>Recalculation price:</strong> PKR {{ number_format($pricePerLiter, 2) }} / L
        </div>
    @else
        <div class="alert alert-warning mb-3">No selling price set for this product.</div>
    @endif

    <form method="POST" action="{{ route('owner-fuel-usages.update', $usage) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Nozzle</label>
            <select name="nozzle_id" id="nozzleSelect" class="form-control" required>
                @foreach($nozzles as $n)
                    <option value="{{ $n->id }}"
                            data-price="{{ \App\Services\ProductPriceService::getPricePerLiter($n->product_id, $usage->usage_datetime) ?? '' }}"
                            @selected(old('nozzle_id', $usage->nozzle_id) == $n->id)>
                        {{ $n->nozzle_number }} — {{ $n->product->name ?? '-' }}
                        (stock: {{ number_format($n->tank->current_stock_liters ?? 0, 2) }} L)
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Employee</label>
            <select name="employee_id" class="form-control">
                <option value="">— None —</option>
                @foreach($employees as $e)
                    <option value="{{ $e->id }}" @selected(old('employee_id', $usage->employee_id) == $e->id)>{{ $e->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Person Name</label>
            <input type="text" name="person_name" class="form-control" value="{{ old('person_name', $usage->person_name) }}">
        </div>

        <div class="mb-3">
            <label>Vehicle No</label>
            <input type="text" name="vehicle_no" class="form-control" value="{{ old('vehicle_no', $usage->vehicle_no) }}">
        </div>

        <div class="mb-3">
            <label>Purpose</label>
            <input type="text" name="purpose" class="form-control" value="{{ old('purpose', $usage->purpose) }}">
        </div>

        <div class="mb-3">
            <label>Liters</label>
            <input type="number" step="0.01" name="liters" id="litersInput" class="form-control"
                   value="{{ old('liters', $usage->liters) }}" required>
        </div>

        <div class="mb-3">
            <label>Total Amount (PKR)</label>
            <div id="totalAmountDisplay" class="form-control bg-light fw-bold fs-5 text-success" style="height:auto;padding:12px 14px;">
                {{ money($usage->total_amount) }}
            </div>
            <small class="text-muted">Recalculated from liters × current price for the usage date.</small>
        </div>

        <div class="mb-3">
            <label>Usage Date & Time</label>
            <input type="datetime-local" name="usage_datetime" class="form-control"
                   value="{{ old('usage_datetime', $usage->usage_datetime?->format('Y-m-d\TH:i')) }}">
        </div>

        <div class="mb-3">
            <label>Notes</label>
            <textarea name="notes" class="form-control">{{ old('notes', $usage->notes) }}</textarea>
        </div>

        <button class="btn btn-primary">Save Changes</button>
        <a href="{{ route('owner-fuel-usages.index') }}" class="btn btn-secondary">Back</a>
    </form>
</div>

<script>
(function () {
    const nozzleSelect = document.getElementById('nozzleSelect');
    const litersInput = document.getElementById('litersInput');
    const totalDisplay = document.getElementById('totalAmountDisplay');

    function updateTotal() {
        const liters = parseFloat(litersInput.value) || 0;
        const price = parseFloat(nozzleSelect.selectedOptions[0]?.dataset.price || '');
        if (liters > 0 && !Number.isNaN(price) && price > 0) {
            totalDisplay.textContent = (liters * price).toLocaleString('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            totalDisplay.className = 'form-control bg-light fw-bold fs-5 text-success';
        } else {
            totalDisplay.textContent = '—';
            totalDisplay.className = 'form-control bg-light fw-bold fs-5 text-muted';
        }
    }

    nozzleSelect.addEventListener('change', updateTotal);
    litersInput.addEventListener('input', updateTotal);
    updateTotal();
})();
</script>

@endsection
