@extends('layouts.app')

@section('content')
<div class="page-card">
    <h3 class="page-title">Record Mobil Oil Sale</h3>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('mobil-oil.sales.store') }}">
        @csrf

        <div class="mb-3">
            <label>Product</label>
            <select name="mobil_oil_product_id" id="productSelect" class="form-control" required>
                @foreach($products as $p)
                    <option value="{{ $p->id }}" data-price="{{ $p->latestPrice?->price ?? '' }}" data-stock="{{ $p->current_stock_qty }}" data-unit="{{ $p->unit }}">
                        {{ $p->name }} — Stock: {{ number_format($p->current_stock_qty, 2) }} {{ $p->unit }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Quantity</label>
            <input type="number" step="0.01" name="quantity" id="quantityInput" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Unit Price (PKR) <small class="text-muted">Leave blank to use current selling price</small></label>
            <input type="number" step="0.01" name="unit_price" id="unitPriceInput" class="form-control">
        </div>

        <div class="mb-3">
            <label>Total Amount (PKR)</label>
            <div id="totalAmountDisplay" class="form-control bg-light fw-bold fs-5 text-success" style="height: auto; padding: 12px 14px;">
                0.00
            </div>
        </div>

        <div class="mb-3">
            <label>Payment Method</label>
            <select name="payment_method" class="form-control">
                <option value="cash">Cash</option>
                <option value="online">Online</option>
            </select>
        </div>

        <div class="mb-3">
            <label>Employee (optional)</label>
            <select name="employee_id" class="form-control">
                <option value="">— None —</option>
                @foreach($employees as $e)
                    <option value="{{ $e->id }}">{{ $e->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Sold Date & Time</label>
            <input type="datetime-local" name="sold_datetime" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
        </div>

        <div class="mb-3">
            <label>Notes</label>
            <textarea name="notes" class="form-control"></textarea>
        </div>

        <button class="btn btn-success">Save Sale</button>
        <a href="{{ route('mobil-oil.sales.index') }}" class="btn btn-secondary">Back</a>
    </form>
</div>

<script>
const productSelect = document.getElementById('productSelect');
const quantityInput = document.getElementById('quantityInput');
const unitPriceInput = document.getElementById('unitPriceInput');
const totalAmountDisplay = document.getElementById('totalAmountDisplay');

function getEffectiveUnitPrice() {
    const manualPrice = parseFloat(unitPriceInput.value);
    if (!Number.isNaN(manualPrice) && manualPrice > 0) {
        return manualPrice;
    }

    const productPrice = parseFloat(productSelect.selectedOptions[0]?.dataset.price || '');
    return Number.isNaN(productPrice) ? 0 : productPrice;
}

function updateTotalAmount() {
    const quantity = parseFloat(quantityInput.value) || 0;
    const unitPrice = getEffectiveUnitPrice();
    const total = quantity * unitPrice;

    totalAmountDisplay.textContent = total > 0
        ? total.toLocaleString('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        : '0.00';

    totalAmountDisplay.classList.toggle('text-danger', quantity > 0 && unitPrice <= 0);
    totalAmountDisplay.classList.toggle('text-success', quantity > 0 && unitPrice > 0);
}

productSelect.addEventListener('change', function () {
    const price = this.selectedOptions[0]?.dataset.price;
    unitPriceInput.placeholder = price ? price : 'Set price on product first';
    unitPriceInput.value = '';
    updateTotalAmount();
});

quantityInput.addEventListener('input', updateTotalAmount);
unitPriceInput.addEventListener('input', updateTotalAmount);

productSelect.dispatchEvent(new Event('change'));
</script>
@endsection
