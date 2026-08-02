@if(isset($mobilOilBreakdown) && $mobilOilBreakdown->count())
    <h3 style="margin-top: 18px; margin-bottom: 8px;">Mobil Oil Sales &amp; Profit</h3>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Qty</th>
                <th>Sales</th>
                <th>Cash</th>
                <th>Online</th>
                <th>Total Profit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mobilOilBreakdown as $row)
                <tr>
                    <td>{{ $row['product'] }}{{ !empty($row['unit']) ? ' ('.$row['unit'].')' : '' }}</td>
                    <td>{{ number_format($row['quantity'], 2) }}</td>
                    <td>{{ money($row['sales_amount']) }}</td>
                    <td>{{ money($row['cash']) }}</td>
                    <td>{{ money($row['online']) }}</td>
                    <td>{{ money($row['total_profit']) }}</td>
                </tr>
            @endforeach
            <tr>
                <td><strong>Total</strong></td>
                <td><strong>{{ number_format($mobilOilBreakdown->sum('quantity'), 2) }}</strong></td>
                <td><strong>{{ money($mobilOilBreakdown->sum('sales_amount')) }}</strong></td>
                <td><strong>{{ money($mobilOilBreakdown->sum('cash')) }}</strong></td>
                <td><strong>{{ money($mobilOilBreakdown->sum('online')) }}</strong></td>
                <td><strong>{{ money($mobilOilBreakdown->sum('total_profit')) }}</strong></td>
            </tr>
        </tbody>
    </table>
@endif
