@extends('layouts.app')

@section('content')

@include('reports.partials.report-styles')

<p class="text-muted mb-3"><small>Snapshot as of {{ $generatedAt }}</small></p>

<div class="row mb-4">
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card amount">
            <div class="info-card-icon"><i class="bi bi-fuel-pump"></i></div>
            <div class="info-card-label">Total Stock</div>
            <div class="info-card-value">{{ number_format($totalStock, 2) }} L</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card secondary">
            <div class="info-card-icon"><i class="bi bi-database"></i></div>
            <div class="info-card-label">Total Capacity</div>
            <div class="info-card-value">{{ number_format($totalCapacity, 2) }} L</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card success">
            <div class="info-card-icon"><i class="bi bi-percent"></i></div>
            <div class="info-card-label">Avg Fill Level</div>
            <div class="info-card-value">{{ number_format($avgFillPercent, 1) }}%</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card {{ $lowStockCount > 0 ? 'danger' : 'success' }}">
            <div class="info-card-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="info-card-label">Low Stock Alerts</div>
            <div class="info-card-value">{{ $lowStockCount }} / {{ $tankCount }}</div>
        </div>
    </div>
</div>

<div class="meta-section">
    <div class="download-section">
        <a href="{{ route('reports.stock.pdf') }}" class="btn-download btn-download-pdf">
            <i class="bi bi-file-pdf"></i> Download PDF
        </a>
        <a href="{{ route('reports.stock.csv') }}" class="btn-download btn-download-excel">
            <i class="bi bi-file-earmark-spreadsheet"></i> Download Excel
        </a>
    </div>
</div>

<div class="table-container">
    @if($tanks->count() > 0)
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Tank</th>
                    <th>Product</th>
                    <th style="text-align: right;">Capacity (L)</th>
                    <th style="text-align: right;">Current Stock (L)</th>
                    <th style="text-align: right;">Available (L)</th>
                    <th>Fill Level</th>
                    <th style="text-align: right;">Min Level (L)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tanks as $t)
                    @php
                        $fillClass = $t['fill_percent'] <= 25 ? 'fill-low' : ($t['fill_percent'] <= 50 ? 'fill-mid' : 'fill-high');
                    @endphp
                    <tr class="{{ $t['is_low'] ? 'row-low' : '' }}">
                        <td><span style="background:#f0f4f8;padding:4px 8px;border-radius:4px;">{{ $t['tank_number'] }}</span></td>
                        <td>{{ $t['product'] }}</td>
                        <td style="text-align: right;">{{ number_format($t['capacity'], 2) }}</td>
                        <td style="text-align: right; font-weight: 600; color: #667eea;">{{ number_format($t['current_stock'], 2) }}</td>
                        <td style="text-align: right;">{{ number_format($t['available'], 2) }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div class="fill-bar {{ $fillClass }}" style="flex:1;">
                                    <span style="width: {{ min(100, $t['fill_percent']) }}%;"></span>
                                </div>
                                <small>{{ $t['fill_percent'] }}%</small>
                            </div>
                        </td>
                        <td style="text-align: right;">{{ number_format($t['minimum_level'], 2) }}</td>
                        <td>
                            @if($t['is_low'])
                                <span class="badge-pill badge-low">Low Stock</span>
                            @else
                                <span class="badge-pill badge-ok">OK</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <p>No tanks configured in the system.</p>
        </div>
    @endif
</div>

@endsection
