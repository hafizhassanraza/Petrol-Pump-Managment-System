@php
    $qty = (float) ($row['liters'] ?? $row['quantity'] ?? 0);
    $amount = (float) ($row['sales_amount'] ?? 0);
    $profit = (float) ($row['total_profit'] ?? 0);
    $showStock = ! empty($row['show_stock']);
    $closeStock = (float) ($row['stock_closing'] ?? 0);
    $hasSale = $qty > 0 || $amount > 0;
@endphp
<div class="stack-cell">
    @if($showStock)
        <div>Close {{ number_format($closeStock, 2) }} L</div>
    @endif

    @if($hasSale)
        <div>{{ $row['sale_rate'] !== null ? rate($row['sale_rate']) : '—' }} × {{ number_format($qty, 2) }}</div>
        <div class="amt">{{ money($amount) }}</div>
        <div class="sub">Profit {{ money($profit) }}</div>
    @elseif(! $showStock)
        —
    @endif
</div>
