<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle ?? 'Fuel Station' }} — Fuel Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; overflow: hidden; }
        body {
            background: #f5f6fa;
            font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        .app-shell { display: flex; height: 100vh; overflow: hidden; }
        .sidebar {
            width: 260px; flex-shrink: 0; height: 100vh; overflow-y: auto;
            background: linear-gradient(180deg, #0f172a 0%, #111827 100%);
            padding-top: 18px; padding-bottom: 24px;
            position: sticky; top: 0; left: 0;
        }
        .sidebar-logo { text-align: center; padding: 8px 16px 20px; pointer-events: none; }
        .sidebar-logo img { max-width: 140px; max-height: 60px; object-fit: contain; }
        .sidebar a {
            color: #cbd5e1; text-decoration: none; display: block;
            padding: 12px 22px; transition: background 0.2s, color 0.2s;
            border-left: 4px solid transparent; margin: 4px 8px; border-radius: 8px;
        }
        .sidebar a:hover { background: rgba(255,255,255,0.03); color: #fff; border-left-color: #10b981; }
        .sidebar a.active {
            background: linear-gradient(90deg, rgba(16,185,129,0.12), rgba(16,185,129,0.06));
            color: #bbf7d0; border-left-color: #10b981;
        }
        .main-column { flex: 1; display: flex; flex-direction: column; min-width: 0; height: 100vh; }
        .topbar {
            flex-shrink: 0; background: #fff; border-bottom: 1px solid #e2e8f0;
            padding: 14px 28px; display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 2px 8px rgba(15,23,42,0.04); z-index: 100;
        }
        .topbar-title { font-size: 20px; font-weight: 700; color: #0f172a; margin: 0; }
        .page-scroll { flex: 1; overflow-y: auto; overflow-x: hidden; }
        .page-content { padding: 20px 28px 40px; }
        .page-card {
            background: #fff; border-radius: 14px; padding: 20px;
            box-shadow: 0 6px 26px rgba(2,6,23,0.06); border: 1px solid #eef2f7; margin-bottom: 18px;
        }
        .list-toolbar { display: flex; justify-content: flex-end; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
        .table-container {
            background: white; border-radius: 10px; overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow-x: auto;
        }
        .excel-table { width: 100%; border-collapse: collapse; font-size: 14px; margin: 0; }
        .excel-table thead { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .excel-table th {
            padding: 15px; text-align: left; font-weight: 600;
            text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; border: none;
        }
        .excel-table td { padding: 12px 15px; border-bottom: 1px solid #e2e8f0; color: #475569; vertical-align: middle; }
        .excel-table tbody tr:hover { background-color: #f8fafc; }
        .excel-table tbody tr:nth-child(even) { background-color: #f9fafc; }
        .filter-section {
            background: white; padding: 20px; border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px;
        }
        .filter-section h5 { color: #1e293b; font-weight: 600; margin-bottom: 15px; font-size: 16px; }
        .filter-options { display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; }
        .filter-btn {
            padding: 8px 16px; border: 2px solid #e2e8f0; background: white; color: #475569;
            border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500;
            text-decoration: none; display: inline-block;
        }
        .filter-btn:hover { border-color: #667eea; color: #667eea; }
        .filter-btn.active { background: #667eea; color: white; border-color: #667eea; }
        .date-range-group { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; }
        .date-input-group label { color: #475569; font-size: 13px; font-weight: 500; display: block; margin-bottom: 5px; }
        .date-input-group input { padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px; }
        .btn-filter { padding: 8px 20px; background: #667eea; color: white; border: none; border-radius: 6px; font-weight: 500; }
        .form-control { border-radius: 8px; border: 1px solid #e6eef6; padding: 10px 12px; }
        .form-control:focus { box-shadow: 0 0 0 4px rgba(102,126,234,0.12); border-color: #667eea; }
        .btn-success { background: linear-gradient(90deg,#10b981,#16a34a); border: none; }
        .status-active { background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .status-inactive { background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .pagination { margin-top: 16px; }
    </style>
    @stack('styles')
</head>
<body>
<div class="app-shell">
    @include('layouts.sidebar')
    <div class="main-column">
        @include('layouts.navbar')
        <div class="page-scroll">
            <div class="page-content">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @yield('content')
            </div>
            @include('layouts.footer')
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
