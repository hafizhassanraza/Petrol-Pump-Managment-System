@extends('layouts.app')

@section('content')

<style>
    .dashboard-header {
        margin-bottom: 30px;
        padding: 24px 22px;
        border-radius: 16px;
        background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
        color: #f8fafc;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12);
    }

    .dashboard-title {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .dashboard-subtitle {
        color: rgba(248, 250, 252, 0.9);
        font-size: 15px;
        line-height: 1.7;
        max-width: 780px;
    }

    .dashboard-grid {
        display: grid;
        gap: 20px;
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }

    @media (min-width: 768px) {
        .dashboard-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 992px) {
        .dashboard-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    .report-card {
        display: flex;
        flex-direction: column;
        min-height: 180px;
        padding: 24px;
        border-radius: 18px;
        color: #0f172a;
        text-decoration: none;
        transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        box-shadow: 0 15px 30px rgba(15, 23, 42, 0.06);
    }

    .report-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 24px 40px rgba(15, 23, 42, 0.12);
        border-color: rgba(15, 23, 42, 0.14);
    }

    .report-card .card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 18px;
    }

    .report-card .card-icon {
        width: 48px;
        height: 48px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        color: white;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.2);
    }

    .report-card.daily .card-icon {
        background: linear-gradient(135deg, #7c3aed 0%, #0ea5e9 100%);
    }

    .report-card.profit .card-icon {
        background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
    }

    .report-card.stock .card-icon {
        background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
    }

    .report-card.expense .card-icon {
        background: linear-gradient(135deg, #ef4444 0%, #f97316 100%);
    }

    .report-card.variance .card-icon {
        background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
    }

    .report-card-title {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .report-card-text {
        color: #475569;
        font-size: 14px;
        line-height: 1.75;
        margin-bottom: 20px;
    }

    .report-card-action {
        margin-top: auto;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 16px;
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.06);
        color: #0f172a;
        font-weight: 600;
        font-size: 14px;
        text-decoration: none;
        transition: background 0.25s ease;
    }

    .report-card-action:hover {
        background: rgba(15, 23, 42, 0.12);
    }
</style>

<div class="dashboard-header">
    <div class="dashboard-title">Reports Dashboard</div>
    <div class="dashboard-subtitle">Quickly navigate to the most important reports with a cleaner dashboard layout. Each report card is styled to match the daily sales page theme.</div>
</div>

<div class="dashboard-grid">
    <a href="{{ route('reports.daily-sales') }}" class="report-card daily">
        <div class="card-top">
            <div>
                <div class="report-card-title">Daily Sales</div>
                <div class="report-card-text">View daily sales details, filters, and export options for PDF or Excel.</div>
            </div>
            <div class="card-icon"><i class="bi bi-calendar-day"></i></div>
        </div>
        <span class="report-card-action">Open report <i class="bi bi-arrow-right-short"></i></span>
    </a>

    <a href="{{ route('reports.profit-loss') }}" class="report-card profit">
        <div class="card-top">
            <div>
                <div class="report-card-title">Profit & Loss</div>
                <div class="report-card-text">Analyze revenue and expenses with a clean profit and loss summary.</div>
            </div>
            <div class="card-icon"><i class="bi bi-currency-dollar"></i></div>
        </div>
        <span class="report-card-action">Open report <i class="bi bi-arrow-right-short"></i></span>
    </a>

    <a href="{{ route('reports.stock') }}" class="report-card stock">
        <div class="card-top">
            <div>
                <div class="report-card-title">Stock Report</div>
                <div class="report-card-text">Check current tank and product stock levels with easy access.</div>
            </div>
            <div class="card-icon"><i class="bi bi-box-seam"></i></div>
        </div>
        <span class="report-card-action">Open report <i class="bi bi-arrow-right-short"></i></span>
    </a>

    <a href="{{ route('reports.expenses') }}" class="report-card expense">
        <div class="card-top">
            <div>
                <div class="report-card-title">Expense Report</div>
                <div class="report-card-text">Review expense entries and track spending with a polished report card.</div>
            </div>
            <div class="card-icon"><i class="bi bi-wallet2"></i></div>
        </div>
        <span class="report-card-action">Open report <i class="bi bi-arrow-right-short"></i></span>
    </a>

    <a href="{{ route('reports.variance') }}" class="report-card variance">
        <div class="card-top">
            <div>
                <div class="report-card-title">Variance Report</div>
                <div class="report-card-text">Compare actual results against targets for better decision-making.</div>
            </div>
            <div class="card-icon"><i class="bi bi-bar-chart"></i></div>
        </div>
        <span class="report-card-action">Open report <i class="bi bi-arrow-right-short"></i></span>
    </a>
</div>

@endsection