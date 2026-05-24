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
        }

        .sidebar{
            width: 250px;
            min-height: 100vh;
            background: #1e293b;
        }

        .sidebar a{
            color: #cbd5e1;
            text-decoration: none;
            display: block;
            padding: 12px 20px;
            transition: 0.3s;
        }

        .sidebar a:hover{
            background: #334155;
            color: white;
        }

        .content{
            width: 100%;

        }

        .page-content{
            width: 100%;
            padding-left: 20px;
            padding-right: 20px;
        }

        .card-box{
            border-radius: 10px;
            padding: 20px;
            color: white;
        }

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