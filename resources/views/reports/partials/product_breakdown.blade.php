@php
    $rows = collect([
        $productBreakdown['petrol'] ?? null,
        $productBreakdown['diesel'] ?? null,
    ])->filter();
    $simple = ! empty($fuelBreakdownSimple);
@endphp
@if($rows->isNotEmpty())
@php
    $fmt = function (?array $row, string $field, string $format = 'money') {
        if ($row === null || ! array_key_exists($field, $row) || $row[$field] === null) {
            return '—';
        }
        $value = $row[$field];

        return match ($format) {
            'liters' => number_format((float) $value, 2),
            'rate', 'profit_l' => $format === 'rate' ? rate($value) : number_format((float) $value, 2),
            default => money($value),
        };
    };
@endphp
<div class="table-container {{ $wrapperClass ?? 'mt-4' }}">
    <h5 class="section-heading p-3 mb-0" style="font-size:16px;font-weight:600;color:#1e293b;">
        <i class="bi bi-fuel-pump"></i> Petroleum Sales &amp; Profit
    </h5>
    <table class="excel-table">
        <thead>
            <tr>
                <th>Fuel</th>
                <th style="text-align: right;">Liters</th>
                <th style="text-align: right;">Sales (PKR)</th>
                <th style="text-align: right;">Cash</th>
                <th style="text-align: right;">Online</th>
                @unless($simple)
                    <th style="text-align: right;">Purchase Rate</th>
                    <th style="text-align: right;">Sale Rate</th>
                    <th style="text-align: right;">Profit / L</th>
                @endunless
                <th style="text-align: right;">Total Profit</th>
                @unless($simple)
                    <th style="text-align: right;">Closing Stock (L)</th>
                    <th style="text-align: right;">Closing Balance (PKR)</th>
                @endunless
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td><strong>{{ $row['product'] }}</strong></td>
                    <td style="text-align: right;">{{ $fmt($row, 'liters', 'liters') }}</td>
                    <td style="text-align: right; font-weight: 600; color: #667eea;">{{ $fmt($row, 'sales_amount') }}</td>
                    <td style="text-align: right;">{{ $fmt($row, 'cash') }}</td>
                    <td style="text-align: right;">{{ $fmt($row, 'online') }}</td>
                    @unless($simple)
                        <td style="text-align: right;">{{ $fmt($row, 'purchase_rate', 'rate') }}</td>
                        <td style="text-align: right;">{{ $fmt($row, 'sale_rate', 'rate') }}</td>
                        <td style="text-align: right;">{{ $fmt($row, 'profit_per_liter', 'profit_l') }}</td>
                    @endunless
                    <td style="text-align: right; font-weight: 600;" class="{{ ($row['total_profit'] ?? 0) >= 0 ? 'text-profit' : 'text-loss' }}">
                        {{ $fmt($row, 'total_profit') }}
                    </td>
                    @unless($simple)
                        <td style="text-align: right;">{{ $fmt($row, 'closing_stock_liters', 'liters') }}</td>
                        <td style="text-align: right;">{{ $fmt($row, 'closing_balance') }}</td>
                    @endunless
                </tr>
            @endforeach
            <tr style="background:#f8fafc; font-weight:600;">
                <td>Total</td>
                <td style="text-align: right;">{{ number_format($rows->sum('liters'), 2) }}</td>
                <td style="text-align: right;">{{ money($rows->sum('sales_amount')) }}</td>
                <td style="text-align: right;">{{ money($rows->sum('cash')) }}</td>
                <td style="text-align: right;">{{ money($rows->sum('online')) }}</td>
                @unless($simple)
                    <td style="text-align: right;">—</td>
                    <td style="text-align: right;">—</td>
                    <td style="text-align: right;">—</td>
                @endunless
                <td style="text-align: right;">{{ money($rows->sum('total_profit')) }}</td>
                @unless($simple)
                    <td style="text-align: right;">{{ number_format($rows->sum('closing_stock_liters'), 2) }}</td>
                    <td style="text-align: right;">{{ money($rows->sum(fn ($r) => $r['closing_balance'] ?? 0)) }}</td>
                @endunless
            </tr>
        </tbody>
    </table>
</div>
@endif
