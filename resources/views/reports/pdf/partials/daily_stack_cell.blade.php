@php
    $segments = collect($row['segments'] ?? [])
        ->filter(fn ($s) => ((float) ($s['liters'] ?? 0)) > 0 || ((float) ($s['sales_amount'] ?? 0)) > 0)
        ->values();

    if ($segments->isEmpty() && (((float) ($row['liters'] ?? $row['quantity'] ?? 0)) > 0 || ((float) ($row['sales_amount'] ?? 0)) > 0)) {
        $segments = collect([[
            'liters' => (float) ($row['liters'] ?? $row['quantity'] ?? 0),
            'sales_amount' => (float) ($row['sales_amount'] ?? 0),
            'sale_rate' => $row['sale_rate'] ?? null,
            'total_profit' => (float) ($row['total_profit'] ?? 0),
        ]]);
    }

    $showStock = ! empty($row['show_stock']);
    $closeStock = (float) ($row['stock_closing'] ?? 0);
    $hasSale = $segments->isNotEmpty();
@endphp
<div class="stack-cell">
    @if($showStock)
        <div>Close {{ number_format($closeStock, 2) }} L</div>
    @endif

    @if($hasSale)
        @foreach($segments as $segment)
            @php
                $qty = (float) ($segment['liters'] ?? $segment['quantity'] ?? 0);
                $amount = (float) ($segment['sales_amount'] ?? 0);
                $profit = (float) ($segment['total_profit'] ?? 0);
            @endphp
            <div @if(! $loop->first) style="margin-top:6px;padding-top:4px;border-top:1px solid #cbd5e1;" @endif>
                <div>{{ $segment['sale_rate'] !== null ? rate($segment['sale_rate']) : '—' }} × {{ number_format($qty, 2) }}</div>
                <div class="amt">{{ money($amount) }}</div>
                <div class="sub">Profit {{ money($profit) }}</div>
            </div>
        @endforeach
    @elseif(! $showStock)
        —
    @endif
</div>
