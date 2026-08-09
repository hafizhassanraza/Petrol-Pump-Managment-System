{{-- Owner + Agency fuel blocks (close + edit) --}}
@php
    $ownerUsage = $ownerUsage ?? ($shift->ownerFuelUsage ?? null);
    $agencyCredit = $agencyCredit ?? ($shift->agencyFuelCredit ?? null);
    $ownerChecked = old('has_owner_fuel', $ownerUsage ? '1' : null);
    $agencyChecked = old('has_agency_fuel', $agencyCredit ? '1' : null);
    $stationPrice = isset($pricePerLiter) && $pricePerLiter ? (float) $pricePerLiter : 0;
    $defaultAgencyPrice = old(
        'agency_sale_price',
        $agencyCredit->price_per_liter ?? ($stationPrice > 0 ? $stationPrice : '')
    );
@endphp

<div class="border rounded-3 p-3 mb-3">
    <div class="form-check mb-2">
        <input class="form-check-input"
               type="checkbox"
               name="has_owner_fuel"
               id="hasOwnerFuel"
               value="1"
               @checked($ownerChecked)>
        <label class="form-check-label fw-semibold" for="hasOwnerFuel">
            Owner fuel used on this nozzle
        </label>
    </div>
    <p class="small text-muted mb-2">Not sold to customers. Liters leave stock but are excluded from cash sales amount.</p>
    <div id="ownerFuelFields" style="display: @if($ownerChecked) block @else none @endif;">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Owner liters *</label>
                <input type="number"
                       step="0.01"
                       min="0.1"
                       name="owner_fuel_liters"
                       id="ownerFuelLitersInput"
                       class="form-control @error('owner_fuel_liters') is-invalid @enderror"
                       value="{{ old('owner_fuel_liters', $ownerUsage->liters ?? '') }}"
                       placeholder="0.00">
                @error('owner_fuel_liters')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Person</label>
                <input type="text"
                       name="owner_person_name"
                       class="form-control"
                       value="{{ old('owner_person_name', $ownerUsage->person_name ?? 'Owner') }}">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Vehicle no</label>
                <input type="text"
                       name="owner_vehicle_no"
                       class="form-control"
                       value="{{ old('owner_vehicle_no', $ownerUsage->vehicle_no ?? '') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Purpose</label>
                <input type="text"
                       name="owner_purpose"
                       class="form-control"
                       value="{{ old('owner_purpose', $ownerUsage->purpose ?? '') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Notes</label>
                <input type="text"
                       name="owner_notes"
                       class="form-control"
                       value="{{ old('owner_notes', $ownerUsage->notes ?? '') }}">
            </div>
        </div>
    </div>
</div>

<div class="border rounded-3 p-3 mb-3">
    <div class="form-check mb-2">
        <input class="form-check-input"
               type="checkbox"
               name="has_agency_fuel"
               id="hasAgencyFuel"
               value="1"
               @checked($agencyChecked)>
        <label class="form-check-label fw-semibold" for="hasAgencyFuel">
            Agency customer credit (borrow fuel, pay later)
        </label>
    </div>
    <p class="small text-muted mb-2">
        Station pump price:
        <strong id="stationPriceLabel">
            @if($stationPrice > 0)
                PKR {{ number_format($stationPrice, 2) }} / L
            @else
                not set
            @endif
        </strong>
        — you can set a different agency sale price below.
    </p>
    <div id="agencyFuelFields" style="display: @if($agencyChecked) block @else none @endif;">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Agency customer *</label>
                <select name="agency_customer_id" class="form-control @error('agency_customer_id') is-invalid @enderror">
                    <option value="">— Select —</option>
                    @foreach($agencyCustomers as $customer)
                        <option value="{{ $customer->id }}"
                            @selected(old('agency_customer_id', $agencyCredit->agency_customer_id ?? null) == $customer->id)>
                            {{ $customer->name }}
                        </option>
                    @endforeach
                </select>
                @error('agency_customer_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <small class="text-muted">
                    <a href="{{ route('agency-customers.create') }}" target="_blank">Add customer</a>
                </small>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Agency liters *</label>
                <input type="number"
                       step="0.01"
                       min="0.1"
                       name="agency_fuel_liters"
                       id="agencyFuelLitersInput"
                       class="form-control @error('agency_fuel_liters') is-invalid @enderror"
                       value="{{ old('agency_fuel_liters', $agencyCredit->liters ?? '') }}"
                       placeholder="0.00">
                @error('agency_fuel_liters')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Agency sale price (PKR / L) *</label>
                <input type="number"
                       step="0.01"
                       min="0.01"
                       name="agency_sale_price"
                       id="agencySalePriceInput"
                       class="form-control @error('agency_sale_price') is-invalid @enderror"
                       value="{{ $defaultAgencyPrice }}"
                       placeholder="{{ $stationPrice > 0 ? number_format($stationPrice, 2, '.', '') : '0.00' }}">
                @error('agency_sale_price')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <small class="text-muted">Defaults to station price; change if agency rate differs.</small>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Notes</label>
                <input type="text"
                       name="agency_notes"
                       class="form-control"
                       value="{{ old('agency_notes', $agencyCredit->notes ?? '') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Credit amount (preview)</label>
                <div id="agencyCreditAmountDisplay" class="form-control bg-light fw-bold text-success" style="height:auto;padding:10px 12px;">
                    —
                </div>
                <small class="text-muted">Liters × agency sale price. Collected later from Agency Customers.</small>
            </div>
        </div>
    </div>
</div>
