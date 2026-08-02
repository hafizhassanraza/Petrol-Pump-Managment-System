{{--
  Fuel cell: Close stock, rate × qty, amount, Profit.
  Mobil Oil: rate × qty, amount, Profit (no stock).
--}}
@php
    $qty = (float) ($row['liters'] ?? $row['quantity'] ?? 0);
    $amount = (float) ($row['sales_amount'] ?? 0);
    $profit = (float) ($row['total_profit'] ?? 0);
    $showStock = ! empty($row['show_stock']);
    $closeStock = (float) ($row['stock_closing'] ?? 0);
    $hasSale = $qty > 0 || $amount > 0;
@endphp
<div class="daily-stack-cell">
    @if($showStock)
        <div class="stack-stock-value">Close {{ number_format($closeStock, 2) }} L</div>
    @endif

    @if($hasSale)
        <div class="stack-rate">{{ $row['sale_rate'] !== null ? rate($row['sale_rate']) : '—' }} × {{ number_format($qty, 2) }}</div>
        <div class="stack-amount">{{ money($amount) }}</div>
        <div class="stack-profit">Profit {{ money($profit) }}</div>
    @elseif(! $showStock)
        <span class="text-muted">—</span>
    @endif
</div>
