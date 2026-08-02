@if(isset($mobilOilBreakdown) && $mobilOilBreakdown->count())
<div class="table-container {{ $wrapperClass ?? 'mt-4' }}">
    <h5 class="section-heading p-3 mb-0" style="font-size:16px;font-weight:600;color:#1e293b;">
        <i class="bi bi-droplet-half"></i> Mobil Oil Sales &amp; Profit
    </h5>
    <table class="excel-table">
        <thead>
            <tr>
                <th>Product</th>
                <th style="text-align: right;">Qty</th>
                <th style="text-align: right;">Sales (PKR)</th>
                <th style="text-align: right;">Cash</th>
                <th style="text-align: right;">Online</th>
                <th style="text-align: right;">Total Profit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mobilOilBreakdown as $row)
                <tr>
                    <td>
                        <strong>{{ $row['product'] }}</strong>
                        @if(!empty($row['unit']))
                            <small class="text-muted">({{ $row['unit'] }})</small>
                        @endif
                    </td>
                    <td style="text-align: right;">{{ number_format($row['quantity'], 2) }}</td>
                    <td style="text-align: right; font-weight: 600; color: #667eea;">{{ money($row['sales_amount']) }}</td>
                    <td style="text-align: right;">{{ money($row['cash']) }}</td>
                    <td style="text-align: right;">{{ money($row['online']) }}</td>
                    <td style="text-align: right; font-weight: 600;" class="{{ ($row['total_profit'] ?? 0) >= 0 ? 'text-profit' : 'text-loss' }}">
                        {{ money($row['total_profit']) }}
                    </td>
                </tr>
            @endforeach
            <tr style="background:#f8fafc; font-weight:600;">
                <td>Total</td>
                <td style="text-align: right;">{{ number_format($mobilOilBreakdown->sum('quantity'), 2) }}</td>
                <td style="text-align: right;">{{ money($mobilOilBreakdown->sum('sales_amount')) }}</td>
                <td style="text-align: right;">{{ money($mobilOilBreakdown->sum('cash')) }}</td>
                <td style="text-align: right;">{{ money($mobilOilBreakdown->sum('online')) }}</td>
                <td style="text-align: right;">{{ money($mobilOilBreakdown->sum('total_profit')) }}</td>
            </tr>
        </tbody>
    </table>
</div>
@endif
