@extends('layouts.app')

@section('content')
<div class="page-card">
    <h3 class="page-title">Record Mobil Oil Purchase</h3>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('mobil-oil.purchases.store') }}">
        @csrf

        <div class="mb-3">
            <label>Product</label>
            <select name="mobil_oil_product_id" class="form-control" required>
                @foreach($products as $p)
                    <option value="{{ $p->id }}">{{ $p->name }} — Stock: {{ number_format($p->current_stock_qty, 2) }} {{ $p->unit }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Invoice No</label>
            <input type="text" name="invoice_no" class="form-control">
        </div>

        <div class="mb-3">
            <label>Quantity</label>
            <input type="number" step="0.01" name="quantity" id="quantityInput" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Purchase Rate (PKR per unit)</label>
            <input type="number" step="0.01" name="purchase_rate" id="purchaseRateInput" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Total Amount (PKR)</label>
            <div id="totalAmountDisplay" class="form-control bg-light fw-bold fs-5 text-success" style="height: auto; padding: 12px 14px;">
                0.00
            </div>
        </div>

        <div class="mb-3">
            <label>Received Date & Time</label>
            <input type="datetime-local" name="received_datetime" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
        </div>

        <div class="mb-3">
            <label>Notes</label>
            <textarea name="notes" class="form-control"></textarea>
        </div>

        <button class="btn btn-success">Save Purchase</button>
        <a href="{{ route('mobil-oil.purchases.index') }}" class="btn btn-secondary">Back</a>
    </form>
</div>

<script>
const quantityInput = document.getElementById('quantityInput');
const purchaseRateInput = document.getElementById('purchaseRateInput');
const totalAmountDisplay = document.getElementById('totalAmountDisplay');

function updateTotalAmount() {
    const quantity = parseFloat(quantityInput.value) || 0;
    const rate = parseFloat(purchaseRateInput.value) || 0;
    const total = quantity * rate;

    totalAmountDisplay.textContent = total > 0
        ? total.toLocaleString('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        : '0.00';

    totalAmountDisplay.classList.toggle('text-success', total > 0);
    totalAmountDisplay.classList.toggle('text-danger', quantity > 0 && rate <= 0);
}

quantityInput.addEventListener('input', updateTotalAmount);
purchaseRateInput.addEventListener('input', updateTotalAmount);
</script>
@endsection
