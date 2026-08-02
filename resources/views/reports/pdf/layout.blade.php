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
            margin-bottom: 20px;
            background: #16a34a;
            color: #fff;
            padding: 16px 18px;
            border-radius: 10px;
        }

        .header-table,
        .header-table td,
        .meta-table,
        .meta-table td {
            border: none !important;
            background: transparent !important;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            padding: 0;
            vertical-align: middle;
            text-align: left;
        }

        .company {
            font-size: 18px;
            font-weight: bold;
            line-height: 1.3;
        }

        .title {
            font-size: 14px;
            margin-top: 4px;
            line-height: 1.3;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 11px;
            color: #334155;
        }

        .meta-table td {
            padding: 0;
            vertical-align: middle;
        }

        .range-info {
            text-align: left;
            font-size: 11px;
            margin-top: 8px;
            margin-bottom: 12px;
            color: #0f172a;
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
    <table class="header-table">
        <tr>
            <td style="width:80px;">
                @if(file_exists(public_path('images/logo.png')))
                    <img src="{{ public_path('images/logo.png') }}" alt="Logo" style="height:60px">
                @endif
            </td>
            <td>
                <div class="company">Fuel Station Management System</div>
                <div class="title">@yield('title')</div>
            </td>
        </tr>
    </table>
</div>

<table class="meta-table">
    <tr>
        <td style="text-align:left;">Generated: {{ date('d M Y H:i') }}</td>
        <td style="text-align:right;">@yield('report-meta')</td>
    </tr>
</table>

@yield('content')

<div class="footer">
    This is a system generated report
</div>

</body>
</html>
