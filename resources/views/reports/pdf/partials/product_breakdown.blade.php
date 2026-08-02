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
            'rate' => rate($value),
            'profit_l' => number_format((float) $value, 2),
            default => money($value),
        };
    };
@endphp
    <h3 style="margin-top: 18px; margin-bottom: 8px;">Petroleum Sales &amp; Profit</h3>
    <table>
        <thead>
            <tr>
                <th>Fuel</th>
                <th>Liters</th>
                <th>Sales</th>
                <th>Cash</th>
                <th>Online</th>
                @unless($simple)
                    <th>Purchase Rate</th>
                    <th>Sale Rate</th>
                    <th>Profit / L</th>
                @endunless
                <th>Total Profit</th>
                @unless($simple)
                    <th>Closing Stock</th>
                    <th>Closing Balance</th>
                @endunless
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td><strong>{{ $row['product'] }}</strong></td>
                    <td>{{ $fmt($row, 'liters', 'liters') }}</td>
                    <td>{{ $fmt($row, 'sales_amount') }}</td>
                    <td>{{ $fmt($row, 'cash') }}</td>
                    <td>{{ $fmt($row, 'online') }}</td>
                    @unless($simple)
                        <td>{{ $fmt($row, 'purchase_rate', 'rate') }}</td>
                        <td>{{ $fmt($row, 'sale_rate', 'rate') }}</td>
                        <td>{{ $fmt($row, 'profit_per_liter', 'profit_l') }}</td>
                    @endunless
                    <td>{{ $fmt($row, 'total_profit') }}</td>
                    @unless($simple)
                        <td>{{ $fmt($row, 'closing_stock_liters', 'liters') }}</td>
                        <td>{{ $fmt($row, 'closing_balance') }}</td>
                    @endunless
                </tr>
            @endforeach
            <tr>
                <td><strong>Total</strong></td>
                <td><strong>{{ number_format($rows->sum('liters'), 2) }}</strong></td>
                <td><strong>{{ money($rows->sum('sales_amount')) }}</strong></td>
                <td><strong>{{ money($rows->sum('cash')) }}</strong></td>
                <td><strong>{{ money($rows->sum('online')) }}</strong></td>
                @unless($simple)
                    <td></td>
                    <td></td>
                    <td></td>
                @endunless
                <td><strong>{{ money($rows->sum('total_profit')) }}</strong></td>
                @unless($simple)
                    <td><strong>{{ number_format($rows->sum('closing_stock_liters'), 2) }}</strong></td>
                    <td><strong>{{ money($rows->sum(fn ($r) => $r['closing_balance'] ?? 0)) }}</strong></td>
                @endunless
            </tr>
        </tbody>
    </table>
@endif
