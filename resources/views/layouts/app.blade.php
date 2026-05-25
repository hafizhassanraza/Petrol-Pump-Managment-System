<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Fuel Station Management System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          rel="stylesheet">

    <style>

        body{
            overflow-x: hidden;
            background: #f5f6fa;
            font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
        }

        /* Sidebar */
        .sidebar{
            width: 260px;
            min-height: 100vh;
            background: linear-gradient(180deg, #0f172a 0%, #111827 100%);
            padding-top: 18px;
        }

        .sidebar a{
            color: #cbd5e1;
            text-decoration: none;
            display: block;
            padding: 12px 22px;
            transition: 0.2s;
            border-left: 4px solid transparent;
            margin: 4px 8px;
            border-radius: 8px;
        }

        .sidebar a:hover{
            background: rgba(255,255,255,0.03);
            color: #fff;
            border-left-color: #10b981;
        }

        .sidebar a.active{
            background: linear-gradient(90deg, rgba(16,185,129,0.12), rgba(16,185,129,0.06));
            color: #bbf7d0;
            border-left-color: #10b981;
            box-shadow: 0 6px 18px rgba(16,185,129,0.06);
        }

        .content{
            width: 100%;
        }

        .page-content{
            width: 100%;
            padding-left: 28px;
            padding-right: 28px;
            padding-top: 20px;
            padding-bottom: 60px;
        }

        /* Global card and table styles (used across reports and modules) */
        .page-card{
            background: #ffffff;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 6px 26px rgba(2,6,23,0.06);
            border: 1px solid #eef2f7;
            margin-bottom: 18px;
        }

        .page-title{font-size:22px; font-weight:700; color:#0f172a;}
        .page-subtitle{color:#64748b; font-size:13px}

        .excel-table thead th, .custom-table thead th{ background: linear-gradient(90deg,#16a34a,#22c55e); color:#fff }

        .custom-table td, .excel-table td{ color: #475569 }

        .btn-primary, .badge.bg-success{ background-color: #16a34a !important; border-color: #16a34a !important }

        .muted { color: #94a3b8 }
        /* Forms and buttons */
        .form-control{
            border-radius: 8px;
            border: 1px solid #e6eef6;
            padding: 10px 12px;
            box-shadow: none;
        }

        .form-control:focus{ box-shadow: 0 0 0 4px rgba(34,197,94,0.08); border-color: #10b981 }

        .btn-success{ background: linear-gradient(90deg,#10b981,#16a34a); border: none }
        .btn-warning{ background: linear-gradient(90deg,#f59e0b,#f97316); border: none }
        .btn-danger{ background: linear-gradient(90deg,#ef4444,#f97316); border: none }

        /* Table responsive */
        .table-responsive{ overflow-x:auto }

    </style>

</head>

<body>

<div class="d-flex">

    {{-- SIDEBAR --}}
    @include('layouts.sidebar')


    <div class="content">

        {{-- NAVBAR --}}
        @include('layouts.navbar')

        <div class="page-content">
            

            {{-- PAGE CONTENT --}}
            @yield('content')


            {{-- FOOTER --}}
            @include('layouts.footer')
        </div>

    </div>

</div>

</body>
</html>