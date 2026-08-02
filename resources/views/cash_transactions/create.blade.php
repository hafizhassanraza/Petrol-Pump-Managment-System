@extends('layouts.app')

@section('content')

<div class="page-card">
    <h3 class="page-title">Add Cash Transaction</h3>
    <p class="page-subtitle">Record cash coming in or going out of the station till.</p>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('cash-transactions.store') }}" id="cashForm">
        @csrf

        <div class="mb-3">
            <label>Type *</label>
            <select name="type" id="typeSelect" class="form-control" required>
                @foreach($types as $value => $label)
                    <option value="{{ $value }}" @selected(old('type', $defaultType) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Category *</label>
            <select name="category" id="categorySelect" class="form-control" required>
                {{-- options filled by JS --}}
            </select>
        </div>

        <div class="mb-3">
            <label>Amount (PKR) *</label>
            <input type="number" step="0.01" min="0.01" name="amount" class="form-control"
                   value="{{ old('amount') }}" required>
        </div>

        <div class="mb-3">
            <label>Date *</label>
            <input type="date" name="transaction_date" class="form-control"
                   value="{{ old('transaction_date', $defaultDate) }}" required>
        </div>

        <div class="mb-3">
            <label>Payment Method *</label>
            <select name="payment_method" class="form-control" required>
                @foreach($paymentMethods as $method)
                    <option value="{{ $method }}" @selected(old('payment_method', 'cash') === $method)>
                        {{ ucfirst($method) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Reference No</label>
            <input type="text" name="reference_no" class="form-control"
                   value="{{ old('reference_no') }}" placeholder="Receipt / voucher no.">
        </div>

        <div class="mb-3">
            <label>Notes</label>
            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
        </div>

        <button class="btn btn-success">Save</button>
        <a href="{{ route('cash-transactions.index') }}" class="btn btn-secondary">Back</a>
    </form>
</div>

<script>
(function () {
    const categories = {
        cash_in: @json($categoriesIn),
        cash_out: @json($categoriesOut),
    };
    const typeSelect = document.getElementById('typeSelect');
    const categorySelect = document.getElementById('categorySelect');
    const oldCategory = @json(old('category'));

    function refreshCategories() {
        const list = categories[typeSelect.value] || [];
        categorySelect.innerHTML = '';
        list.forEach(function (name) {
            const opt = document.createElement('option');
            opt.value = name;
            opt.textContent = name;
            if (oldCategory && oldCategory === name) opt.selected = true;
            categorySelect.appendChild(opt);
        });
    }

    typeSelect.addEventListener('change', function () {
        refreshCategories();
    });
    refreshCategories();
})();
</script>

@endsection
