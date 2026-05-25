<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Report</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .company {
            font-size: 18px;
            font-weight: bold;
        }

        .title {
            font-size: 14px;
            margin-top: 5px;
        }

        .info {
            text-align: right;
            font-size: 11px;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table, th, td {
            border: 1px solid #000;
        }

        th {
            background: #f2f2f2;
        }

        th, td {
            padding: 6px;
            text-align: center;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
        }

    </style>

</head>
<body>

<div class="header">
    <div style="display:flex;align-items:center;justify-content:center;gap:12px;">
        <div style="text-align:left;flex:0 0 80px;">
            @if(file_exists(public_path('images/logo.png')))
                <img src="{{ public_path('images/logo.png') }}" alt="Logo" style="height:60px">
            @endif
        </div>
        <div style="text-align:left;">
            <div class="company">Fuel Station Management System</div>
            <div class="title">@yield('title')</div>
        </div>
    </div>
</div>

<div class="info">
    Generated: {{ date('d M Y H:i') }}
</div>

@yield('content')

<div class="footer">
    This is a system generated report
</div>

</body>
</html>