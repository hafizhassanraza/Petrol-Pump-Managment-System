@extends('layouts.app')

@section('content')

<style>
    .dash-hero {
        margin-bottom: 28px;
        padding: 28px 26px;
        border-radius: 16px;
        background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #16a34a 100%);
        color: #f8fafc;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
    }
    .dash-hero h1 { font-size: 30px; font-weight: 700; margin-bottom: 6px; }
    .dash-hero p { margin: 0; opacity: 0.9; font-size: 14px; }
    .dash-hero .hero-date { font-size: 13px; opacity: 0.75; margin-top: 10px; }
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 14px;
        margin-bottom: 24px;
    }
    .kpi-card {
        border-radius: 14px;
        padding: 18px 16px;
        color: #fff;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.1);
        transition: transform 0.2s;
    }
    .kpi-card:hover { transform: translateY(-3px); }
    .kpi-card .kpi-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.9; }
    .kpi-card .kpi-value { font-size: 26px; font-weight: 700; margin-top: 6px; line-height: 1.2; }
    .kpi-card .kpi-sub { font-size: 11px; opacity: 0.85; margin-top: 4px; }
    .kpi-primary { background: linear-gradient(135deg, #667eea, #764ba2); }
    .kpi-success { background: linear-gradient(135deg, #11998e, #38ef7d); }
    .kpi-danger { background: linear-gradient(135deg, #eb3349, #f45c43); }
    .kpi-warning { background: linear-gradient(135deg, #f7971e, #ffd200); color: #1e293b; }
    .kpi-info { background: linear-gradient(135deg, #2193b0, #6dd5ed); }
    .kpi-dark { background: linear-gradient(135deg, #232526, #414345); }
    .kpi-profit { background: linear-gradient(135deg, #16a34a, #22c55e); }
    .kpi-loss { background: linear-gradient(135deg, #dc2626, #f87171); }
    .section-card {
        background: #fff;
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 6px 26px rgba(2, 6, 23, 0.06);
        border: 1px solid #eef2f7;
        margin-bottom: 20px;
        height: 100%;
    }
    .section-card h5 {
        font-size: 15px;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-card h5 i { color: #16a34a; }
    .chart-wrap { position: relative; min-height: 260px; }
    .chart-wrap.sm { min-height: 220px; }
    .stat-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: 14px;
    }
    .stat-row:last-child { border-bottom: none; }
    .stat-row strong { color: #1e293b; }
    .mini-table { width: 100%; font-size: 13px; }
    .mini-table th {
        background: linear-gradient(90deg, #16a34a, #22c55e);
        color: #fff;
        padding: 10px 12px;
        font-size: 11px;
        text-transform: uppercase;
    }
    .mini-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; color: #475569; }
    .mini-table tr:hover td { background: #f8fafc; }
    .badge-low { background: #fef3c7; color: #92400e; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
    .badge-ok { background: #dcfce7; color: #166534; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
    .text-profit { color: #16a34a; font-weight: 600; }
    .text-loss { color: #dc2626; font-weight: 600; }
    .inventory-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f1f5f9;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 13px;
        color: #475569;
        margin: 4px 6px 4px 0;
    }
    .inventory-pill strong { color: #1e293b; }
    .quick-links a {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        background: #f0fdf4;
        color: #166534;
        border-radius: 8px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        margin: 4px 8px 4px 0;
        border: 1px solid #bbf7d0;
    }
    .quick-links a:hover { background: #dcfce7; }
</style>

<div class="dash-hero">
    <p class="mb-0">Real-time analytics — sales, expenses, inventory, and operations.</p>
    <div class="hero-date"><i class="bi bi-calendar3"></i> {{ now()->format('l, d F Y') }}</div>
</div>

{{-- Primary KPIs --}}
<div class="kpi-grid">
    <div class="kpi-card kpi-profit">
        <div class="kpi-label">Today Sales <small>(9 AM day)</small></div>
        <div class="kpi-value">PKR {{ number_format($todaySales, 0) }}</div>
        <div class="kpi-sub">{{ number_format($todayLiters, 1) }} L &middot; {{ $todayShiftCount }} shifts</div>
    </div>
    <div class="kpi-card kpi-danger">
        <div class="kpi-label">Today Expenses</div>
        <div class="kpi-value">PKR {{ number_format($todayExpense, 0) }}</div>
    </div>
    <div class="kpi-card kpi-warning">
        <div class="kpi-label">Owner Fuel (Today)</div>
        <div class="kpi-value">PKR {{ number_format($todayOwnerFuel, 0) }}</div>
    </div>
    <div class="kpi-card {{ $todayNet >= 0 ? 'kpi-profit' : 'kpi-loss' }}">
        <div class="kpi-label">Today Net</div>
        <div class="kpi-value">PKR {{ number_format($todayNet, 0) }}</div>
        <div class="kpi-sub">{{ $todaySales > 0 ? number_format(($todayNet / $todaySales) * 100, 1) : 0 }}% margin</div>
    </div>
    <div class="kpi-card kpi-primary">
        <div class="kpi-label">MTD Sales</div>
        <div class="kpi-value">PKR {{ number_format($mtdSales, 0) }}</div>
        <div class="kpi-sub">{{ number_format($mtdLiters, 0) }} L this month</div>
    </div>
    <div class="kpi-card {{ $mtdNet >= 0 ? 'kpi-success' : 'kpi-loss' }}">
        <div class="kpi-label">MTD Net Profit</div>
        <div class="kpi-value">PKR {{ number_format($mtdNet, 0) }}</div>
    </div>
</div>

{{-- Inventory + ops KPIs --}}
<div class="kpi-grid">
    <div class="kpi-card kpi-info">
        <div class="kpi-label">Tank Stock</div>
        <div class="kpi-value">{{ number_format($totalTankStock, 0) }} L</div>
        <div class="kpi-sub">of {{ number_format($totalTankCapacity, 0) }} L capacity</div>
    </div>
    <div class="kpi-card {{ $lowStockTanks->count() > 0 ? 'kpi-loss' : 'kpi-success' }}">
        <div class="kpi-label">Low Stock Alerts</div>
        <div class="kpi-value">{{ $lowStockTanks->count() }}</div>
        <div class="kpi-sub">{{ $tanks }} tanks monitored</div>
    </div>
    <div class="kpi-card kpi-dark">
        <div class="kpi-label">Active Shifts</div>
        <div class="kpi-value">{{ $activeShifts }}</div>
    </div>
    <div class="kpi-card kpi-primary">
        <div class="kpi-label">MTD Refills</div>
        <div class="kpi-value">PKR {{ number_format($mtdRefills, 0) }}</div>
    </div>
</div>

<div class="mb-3">
    <span class="inventory-pill"><i class="bi bi-droplet"></i> Products <strong>{{ $products }}</strong></span>
    <span class="inventory-pill"><i class="bi bi-database"></i> Tanks <strong>{{ $tanks }}</strong></span>
    <span class="inventory-pill"><i class="bi bi-hdd-stack"></i> Dispensers <strong>{{ $dispensers }}</strong></span>
    <span class="inventory-pill"><i class="bi bi-funnel"></i> Nozzles <strong>{{ $nozzles }}</strong></span>
    <span class="inventory-pill"><i class="bi bi-people"></i> Employees <strong>{{ $employees }}</strong></span>
</div>

<div class="quick-links mb-4">
    <a href="{{ route('reports.daily-sales') }}"><i class="bi bi-graph-up"></i> Sales Report</a>
    <a href="{{ route('reports.profit-loss') }}"><i class="bi bi-pie-chart"></i> P&amp;L Report</a>
    <a href="{{ route('reports.stock') }}"><i class="bi bi-droplet-half"></i> Stock Report</a>
    <a href="{{ route('reports.expenses') }}"><i class="bi bi-receipt"></i> Expenses</a>
    <a href="{{ route('reports.variance') }}"><i class="bi bi-arrow-left-right"></i> Variance</a>
    <a href="{{ route('employee-shifts.index') }}"><i class="bi bi-clock-history"></i> Shifts</a>
</div>

{{-- Charts row 1 --}}
<div class="row">
    <div class="col-lg-8 mb-3">
        <div class="section-card">
            <h5><i class="bi bi-graph-up-arrow"></i> Sales, Expenses &amp; Net Profit (7 Days)</h5>
            <div class="chart-wrap"><canvas id="trendChart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4 mb-3">
        <div class="section-card">
            <h5><i class="bi bi-credit-card"></i> Today Payments</h5>
            <div class="chart-wrap sm"><canvas id="paymentChart"></canvas></div>
            <div class="stat-row"><span>Cash Received</span><strong>PKR {{ number_format($todayCash, 2) }}</strong></div>
            <div class="stat-row"><span>Online Received</span><strong>PKR {{ number_format($todayOnline, 2) }}</strong></div>
        </div>
    </div>
</div>

{{-- Charts row 2 --}}
<div class="row">
    <div class="col-lg-4 mb-3">
        <div class="section-card">
            <h5><i class="bi bi-fuel-pump"></i> Liters Sold (7 Days)</h5>
            <div class="chart-wrap sm"><canvas id="litersChart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4 mb-3">
        <div class="section-card">
            <h5><i class="bi bi-pie-chart"></i> Sales by Product (7 Days)</h5>
            <div class="chart-wrap sm"><canvas id="productChart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4 mb-3">
        <div class="section-card">
            <h5><i class="bi bi-tags"></i> Expenses by Type (30 Days)</h5>
            <div class="chart-wrap sm"><canvas id="expenseChart"></canvas></div>
        </div>
    </div>
</div>

{{-- Charts row 3 --}}
<div class="row">
    <div class="col-lg-7 mb-3">
        <div class="section-card">
            <h5><i class="bi bi-database"></i> Tank Fill Levels</h5>
            <div class="chart-wrap"><canvas id="tankChart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-5 mb-3">
        <div class="section-card">
            <h5><i class="bi bi-clipboard-data"></i> Month Summary</h5>
            <div class="stat-row"><span>Total Sales</span><strong class="text-profit">PKR {{ number_format($mtdSales, 2) }}</strong></div>
            <div class="stat-row"><span>Total Expenses</span><strong class="text-loss">PKR {{ number_format($mtdExpense, 2) }}</strong></div>
            <div class="stat-row"><span>Owner Fuel Usage</span><strong class="text-loss">PKR {{ number_format($mtdOwnerFuel, 2) }}</strong></div>
            <div class="stat-row"><span>Tank Refill Purchases</span><strong>PKR {{ number_format($mtdRefills, 2) }}</strong></div>
            <div class="stat-row"><span>Liters Sold (MTD)</span><strong>{{ number_format($mtdLiters, 2) }} L</strong></div>
            <div class="stat-row" style="background:#f0fdf4;margin-top:8px;border-radius:8px;padding:12px;">
                <span><strong>Net Profit (MTD)</strong></span>
                <strong class="{{ $mtdNet >= 0 ? 'text-profit' : 'text-loss' }}">PKR {{ number_format($mtdNet, 2) }}</strong>
            </div>
            <hr class="my-3">
            <h5><i class="bi bi-trophy"></i> Top Employees (7 Days)</h5>
            @forelse($topEmployees as $emp)
                <div class="stat-row">
                    <span>{{ $emp['name'] }} <small class="text-muted">({{ $emp['shifts'] }} shifts)</small></span>
                    <strong>PKR {{ number_format($emp['amount'], 0) }}</strong>
                </div>
            @empty
                <p class="text-muted small mb-0">No shift data yet.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- Tables row --}}
<div class="row">
    <div class="col-lg-4 mb-3">
        <div class="section-card">
            <h5><i class="bi bi-clock-history"></i> Recent Shifts</h5>
            <div class="table-responsive">
                <table class="mini-table">
                    <thead><tr><th>Employee</th><th>Amount</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($recentShifts as $s)
                            <tr>
                                <td>{{ $s->employee->name ?? 'N/A' }}</td>
                                <td>{{ number_format($s->total_amount, 0) }}</td>
                                <td><span class="badge-ok">{{ $s->status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-muted">No shifts recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-3">
        <div class="section-card">
            <h5><i class="bi bi-receipt"></i> Recent Expenses</h5>
            <div class="table-responsive">
                <table class="mini-table">
                    <thead><tr><th>Type</th><th>Amount</th><th>Date</th></tr></thead>
                    <tbody>
                        @forelse($recentExpenses as $e)
                            <tr>
                                <td>{{ $e->expense_type }}</td>
                                <td>{{ number_format($e->amount, 0) }}</td>
                                <td>{{ \Carbon\Carbon::parse($e->expense_date)->format('d M') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-muted">No expenses recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-3">
        <div class="section-card">
            <h5><i class="bi bi-exclamation-triangle"></i> Low Stock Tanks</h5>
            @if($lowStockTanks->count() > 0)
                <div class="table-responsive">
                    <table class="mini-table">
                        <thead><tr><th>Tank</th><th>Stock</th><th>Fill</th></tr></thead>
                        <tbody>
                            @foreach($lowStockTanks as $t)
                                <tr>
                                    <td>{{ $t['tank_number'] }}</td>
                                    <td>{{ number_format($t['stock'], 0) }} L</td>
                                    <td><span class="badge-low">{{ $t['fill_percent'] }}%</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted small mb-0"><i class="bi bi-check-circle text-success"></i> All tanks above minimum level.</p>
            @endif
            @if($recentOwnerFuel->count() > 0)
                <hr>
                <h5><i class="bi bi-car-front"></i> Recent Owner Fuel</h5>
                @foreach($recentOwnerFuel as $o)
                    <div class="stat-row">
                        <span>{{ $o->person_name ?? 'Owner' }}</span>
                        <strong>PKR {{ number_format($o->total_amount, 0) }}</strong>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
Chart.defaults.color = '#64748b';

const palette = ['#667eea','#764ba2','#11998e','#38ef7d','#f5576c','#f093fb','#f7971e','#2193b0','#16a34a','#dc2626'];

const trendLabels = @json($trend['labels']);
const trendSales = @json($trend['sales']);
const trendExpenses = @json($trend['expenses']);
const trendOwnerFuel = @json($trend['ownerFuel']);
const trendNet = @json($trend['net']);
const trendLiters = @json($trend['liters']);

new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: trendLabels,
        datasets: [
            { label: 'Sales (PKR)', data: trendSales, borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,0.1)', fill: true, tension: 0.35, yAxisID: 'y' },
            { label: 'Expenses (PKR)', data: trendExpenses, borderColor: '#dc2626', backgroundColor: 'rgba(220,38,38,0.05)', fill: true, tension: 0.35, yAxisID: 'y' },
            { label: 'Owner Fuel (PKR)', data: trendOwnerFuel, borderColor: '#f59e0b', borderDash: [4,4], tension: 0.35, yAxisID: 'y' },
            { label: 'Net Profit (PKR)', data: trendNet, borderColor: '#667eea', borderWidth: 2.5, tension: 0.35, yAxisID: 'y' },
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { position: 'bottom' } },
        scales: {
            y: { beginAtZero: true, ticks: { callback: v => 'PKR ' + v.toLocaleString() } }
        }
    }
});

new Chart(document.getElementById('paymentChart'), {
    type: 'doughnut',
    data: {
        labels: ['Cash', 'Online'],
        datasets: [{
            data: [{{ $todayCash }}, {{ $todayOnline }}],
            backgroundColor: ['#16a34a', '#2193b0'],
            borderWidth: 0
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
});

new Chart(document.getElementById('litersChart'), {
    type: 'bar',
    data: {
        labels: trendLabels,
        datasets: [{ label: 'Liters', data: trendLiters, backgroundColor: 'rgba(102,126,234,0.7)', borderRadius: 6 }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { callback: v => v + ' L' } } }
    }
});

const productLabels = @json($salesByProduct->pluck('product'));
const productAmounts = @json($salesByProduct->pluck('amount'));

if (productLabels.length) {
    new Chart(document.getElementById('productChart'), {
        type: 'doughnut',
        data: {
            labels: productLabels,
            datasets: [{ data: productAmounts, backgroundColor: palette, borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
    });
} else {
    document.getElementById('productChart').parentElement.innerHTML += '<p class="text-muted small text-center">No sales data for last 7 days.</p>';
}

const expenseLabels = @json($expenseByType->pluck('expense_type'));
const expenseAmounts = @json($expenseByType->pluck('total'));

if (expenseLabels.length) {
    new Chart(document.getElementById('expenseChart'), {
        type: 'pie',
        data: {
            labels: expenseLabels,
            datasets: [{ data: expenseAmounts, backgroundColor: palette.slice().reverse(), borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
    });
} else {
    document.getElementById('expenseChart').parentElement.innerHTML += '<p class="text-muted small text-center">No expenses in last 30 days.</p>';
}

const tankLabels = @json($tankStock->pluck('label'));
const tankFill = @json($tankStock->pluck('fill_percent'));

if (tankLabels.length) {
    new Chart(document.getElementById('tankChart'), {
        type: 'bar',
        data: {
            labels: tankLabels,
            datasets: [{
                label: 'Fill %',
                data: tankFill,
                backgroundColor: tankFill.map(v => v <= 25 ? '#ef4444' : (v <= 50 ? '#f59e0b' : '#22c55e')),
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { max: 100, ticks: { callback: v => v + '%' } } }
        }
    });
}
</script>

@endsection
