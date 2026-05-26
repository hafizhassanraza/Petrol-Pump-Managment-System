<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Fuel Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh; margin: 0;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 45%, #16a34a 100%);
            display: flex; align-items: center; justify-content: center;
            font-family: Inter, system-ui, sans-serif; padding: 24px;
        }
        .login-card {
            width: 100%; max-width: 420px; background: #fff; border-radius: 20px;
            padding: 40px 36px; box-shadow: 0 25px 50px rgba(0,0,0,0.25);
        }
        .login-logo { text-align: center; margin-bottom: 24px; }
        .login-logo img { max-height: 70px; max-width: 200px; object-fit: contain; }
        .login-title { font-size: 24px; font-weight: 700; color: #0f172a; text-align: center; margin-bottom: 8px; }
        .login-subtitle { text-align: center; color: #64748b; font-size: 14px; margin-bottom: 28px; }
        .btn-login {
            width: 100%; padding: 12px; background: linear-gradient(90deg, #10b981, #16a34a);
            border: none; color: white; font-weight: 600; border-radius: 10px;
        }
        .btn-login:hover { opacity: 0.95; color: white; }
        .form-control { border-radius: 10px; padding: 12px 14px; border: 1px solid #e2e8f0; }
        .form-control:focus { border-color: #16a34a; box-shadow: 0 0 0 4px rgba(22,163,74,0.15); }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-logo">
            <img src="{{ asset('images/logo.png') }}" alt="Fuel Station">
        </div>
        <h1 class="login-title">{{ config('portfolio.station_name') }}</h1>
        <p class="login-subtitle">Fuel Station Management Portal · {{ config('portfolio.brand') }}</p>

        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', 'admin@example.com') }}" required autofocus>
                @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
                @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="form-check mb-4">
                <input type="checkbox" name="remember" class="form-check-input" id="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>
            <button type="submit" class="btn btn-login">Sign In</button>
        </form>
        <div class="text-center mt-4">
            <a href="{{ route('home') }}" class="text-muted small text-decoration-none">
                <i class="bi bi-arrow-left"></i> Back to {{ config('portfolio.station_name') }}
            </a>
        </div>
    </div>
</body>
</html>
