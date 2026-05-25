@extends('layouts.app')

@section('content')

@include('reports.partials.report-styles')

<p class="text-muted mb-3"><small>System vs physical dip &mdash; {{ $generatedAt }}</small></p>

<div class="row mb-4">
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card amount">
            <div class="info-card-icon"><i class="bi bi-check-circle"></i></div>
            <div class="info-card-label">Matched Tanks</div>
            <div class="info-card-value">{{ $matchedCount }}</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card secondary">
            <div class="info-card-icon"><i class="bi bi-exclamation-circle"></i></div>
            <div class="info-card-label">With Variance</div>
            <div class="info-card-value">{{ $tanksWithVariance }}</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card {{ abs($totalVariance) < 0.01 ? 'success' : 'warning' }}">
            <div class="info-card-icon"><i class="bi bi-speedometer2"></i></div>
            <div class="info-card-label">Total Variance</div>
            <div class="info-card-value">{{ number_format($totalVariance, 2) }} L</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="info-card success">
            <div class="info-card-icon"><i class="bi bi-droplet"></i></div>
            <div class="info-card-label">Total Tanks</div>
            <div class="info-card-value">{{ $tankCount }}</div>
        </div>
    </div>
</div>

<div class="meta-section">
    <div class="download-section">
        <a href="{{ route('reports.variance.pdf') }}" class="btn-download btn-download-pdf">
            <i class="bi bi-file-pdf"></i> Download PDF
        </a>
        <a href="{{ route('reports.variance.csv') }}" class="btn-download btn-download-excel">
            <i class="bi bi-file-earmark-spreadsheet"></i> Download Excel
        </a>
    </div>
</div>

<div class="table-container">
    @if($variances->count() > 0)
        <table class="excel-table">
            <thead>
                <tr>
                    <th>Tank</th>
                    <th>Product</th>
                    <th style="text-align: right;">System (L)</th>
                    <th style="text-align: right;">Physical (L)</th>
                    <th style="text-align: right;">Difference (L)</th>
                    <th>Status</th>
                    <th>Last Dip Reading</th>
                </tr>
            </thead>
            <tbody>
                @foreach($variances as $v)
                    <tr>
                        <td><span style="background:#f0f4f8;padding:4px 8px;border-radius:4px;">{{ $v['tank_number'] }}</span></td>
                        <td>{{ $v['product'] }}</td>
                        <td style="text-align: right;">{{ number_format($v['system'], 2) }}</td>
                        <td style="text-align: right;">{{ number_format($v['physical'], 2) }}</td>
                        <td style="text-align: right; font-weight: 600;" class="{{ $v['difference'] > 0 ? 'text-profit' : ($v['difference'] < 0 ? 'text-loss' : '') }}">
                            {{ $v['difference'] > 0 ? '+' : '' }}{{ number_format($v['difference'], 2) }}
                        </td>
                        <td>
                            <span class="badge-pill badge-{{ $v['status'] }}">{{ $v['status_label'] }}</span>
                        </td>
                        <td>
                            @if($v['has_dip'])
                                {{ $v['dip_date'] }}
                            @else
                                <span style="color:#94a3b8;">No dip reading</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <p>No tanks available for variance comparison.</p>
        </div>
    @endif
</div>

@endsection
