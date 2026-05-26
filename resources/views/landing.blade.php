<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $stationName }} ({{ $brand }})</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --hascol-green: #00843d;
            --hascol-dark: #0a3d20;
            --hascol-gold: #f4c430;
            --text-dark: #0f172a;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: var(--text-dark);
            background: #f8fafc;
        }
        .landing-nav {
            position: sticky; top: 0; z-index: 100;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #e2e8f0;
            padding: 14px 0;
        }
        .nav-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; }
        .nav-brand img { height: 48px; object-fit: contain; }
        .nav-brand-text strong { display: block; font-size: 18px; color: var(--hascol-dark); line-height: 1.2; }
        .nav-brand-text small { color: var(--hascol-green); font-weight: 600; letter-spacing: 0.5px; }
        .hero {
            background: linear-gradient(135deg, var(--hascol-dark) 0%, var(--hascol-green) 55%, #16a34a 100%);
            color: #fff; padding: 80px 0 100px; position: relative; overflow: hidden;
        }
        .hero::after {
            content: ''; position: absolute; right: -80px; top: -80px;
            width: 400px; height: 400px; border-radius: 50%;
            background: rgba(255,255,255,0.06);
        }
        .hero-badge {
            display: inline-block; background: rgba(244,196,48,0.2);
            border: 1px solid rgba(244,196,48,0.5); color: var(--hascol-gold);
            padding: 6px 14px; border-radius: 30px; font-size: 13px; font-weight: 600;
            margin-bottom: 20px;
        }
        .hero h1 { font-size: clamp(2rem, 5vw, 3.2rem); font-weight: 800; margin-bottom: 16px; }
        .hero p.lead { font-size: 1.15rem; opacity: 0.92; max-width: 560px; line-height: 1.7; }
        .hero-stats { display: flex; gap: 32px; margin-top: 40px; flex-wrap: wrap; }
        .hero-stat strong { display: block; font-size: 1.5rem; }
        .hero-stat span { font-size: 13px; opacity: 0.85; }
        .section { padding: 72px 0; }
        .section-title { font-size: 28px; font-weight: 700; margin-bottom: 12px; }
        .section-sub { color: #64748b; margin-bottom: 40px; max-width: 600px; }
        .service-card {
            background: #fff; border-radius: 16px; padding: 28px 24px;
            border: 1px solid #e2e8f0; height: 100%;
            box-shadow: 0 4px 20px rgba(15,23,42,0.04);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .service-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(15,23,42,0.08); }
        .service-icon {
            width: 52px; height: 52px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; margin-bottom: 16px;
        }
        .service-icon.fuel { background: #dcfce7; color: var(--hascol-green); }
        .service-icon.shop { background: #fef3c7; color: #b45309; }
        .service-icon.care { background: #dbeafe; color: #1d4ed8; }
        .portal-section { background: linear-gradient(180deg, #f1f5f9 0%, #fff 100%); }
        .portal-card {
            background: #fff; border-radius: 20px; overflow: hidden;
            border: 1px solid #e2e8f0; box-shadow: 0 8px 30px rgba(15,23,42,0.06);
            height: 100%; display: flex; flex-direction: column;
        }
        .portal-card-header {
            padding: 28px 28px 20px; color: #fff;
        }
        .portal-card-header.fuel {
            background: linear-gradient(135deg, var(--hascol-dark), var(--hascol-green));
        }
        .portal-card-header.shop {
            background: linear-gradient(135deg, #92400e, #f59e0b);
        }
        .portal-card-body { padding: 24px 28px 28px; flex: 1; display: flex; flex-direction: column; }
        .portal-card-body p { color: #64748b; flex: 1; margin-bottom: 20px; line-height: 1.6; }
        .btn-portal {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 14px 24px; border-radius: 12px; font-weight: 600;
            text-decoration: none; transition: opacity 0.2s, transform 0.2s;
        }
        .btn-portal:hover { opacity: 0.95; transform: translateY(-1px); color: #fff; }
        .btn-portal-fuel { background: var(--hascol-green); color: #fff; }
        .btn-portal-shop { background: #ea580c; color: #fff; }
        .about-box {
            background: #fff; border-radius: 16px; padding: 32px;
            border-left: 5px solid var(--hascol-green);
            box-shadow: 0 4px 20px rgba(15,23,42,0.05);
        }
        .contact-item { display: flex; gap: 12px; align-items: flex-start; margin-bottom: 16px; }
        .contact-item i { color: var(--hascol-green); font-size: 20px; margin-top: 2px; }
        .landing-footer {
            background: var(--hascol-dark); color: rgba(255,255,255,0.85);
            padding: 32px 0; font-size: 14px;
        }
        .brand-pill {
            display: inline-block; background: var(--hascol-green); color: #fff;
            padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 700;
        }
    </style>
</head>
<body>

<nav class="landing-nav">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('home') }}" class="nav-brand">
                <img src="{{ asset('images/logo.png') }}" alt="{{ $stationName }}">
                <div class="nav-brand-text">
                    <strong>{{ $stationName }}</strong>
                    <small>{{ $brand }} Authorized Dealer</small>
                </div>
            </a>
            <div class="d-none d-md-flex gap-2">
                <a href="#portals" class="btn btn-outline-success btn-sm">Portals</a>
                <a href="{{ route('login') }}" class="btn btn-success btn-sm">Staff Login</a>
            </div>
        </div>
    </div>
</nav>

<section class="hero">
    <div class="container position-relative" style="z-index:1;">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <span class="hero-badge"><i class="bi bi-shield-check"></i> {{ $brand }} Authorized Station</span>
                <h1>{{ $stationName }}</h1>
                <p class="lead">{{ $tagline }}</p>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <strong><i class="bi bi-fuel-pump"></i> Premium Fuel</strong>
                        <span>Petrol · Diesel</span>
                    </div>
                    <div class="hero-stat">
                        <strong><i class="bi bi-shop"></i> Tuck Shop</strong>
                        <span>Snacks &amp; daily essentials</span>
                    </div>
                    <div class="hero-stat">
                        <strong><i class="bi bi-clock"></i> Open Daily</strong>
                        <span>Reliable service</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block text-center">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" style="max-width:280px; filter: drop-shadow(0 20px 40px rgba(0,0,0,0.3));">
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <h2 class="section-title">What We Offer</h2>
        <p class="section-sub">Your trusted neighborhood fuel station with quality products and friendly service.</p>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon fuel"><i class="bi bi-droplet-fill"></i></div>
                    <h5>Quality Fuel</h5>
                    <p class="text-muted mb-0">Authorized {{ $brand }} dealership offering clean, reliable fuel for cars, bikes, and commercial vehicles.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon shop"><i class="bi bi-basket-fill"></i></div>
                    <h5>Tuck Shop</h5>
                    <p class="text-muted mb-0">Convenient tuck shop for refreshments, snacks, and everyday items while you refuel.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon care"><i class="bi bi-people-fill"></i></div>
                    <h5>Professional Team</h5>
                    <p class="text-muted mb-0">Trained staff focused on safety, accurate dispensing, and customer satisfaction.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section about-box mx-3 mx-md-auto" style="max-width:1140px;">
    <div class="container px-0">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="brand-pill mb-2">{{ $brand }}</span>
                <h2 class="section-title mb-3">About {{ $stationName }}</h2>
                <p class="text-muted mb-0" style="line-height:1.8;">
                    {{ $stationName }} is a {{ $brand }}-authorized petroleum outlet committed to delivering
                    quality fuel and a smooth customer experience. Our station combines modern dispensing
                    with a well-stocked tuck shop — everything you need in one stop.
                </p>
            </div>
            <div class="col-lg-4">
                @if($phone || $address)
                    <h6 class="fw-bold mb-3">Contact</h6>
                    @if($phone)
                        <div class="contact-item">
                            <i class="bi bi-telephone-fill"></i>
                            <span>{{ $phone }}</span>
                        </div>
                    @endif
                    @if($address)
                        <div class="contact-item">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span>{{ $address }}</span>
                        </div>
                    @endif
                @else
                    <p class="text-muted small mb-0">
                        <i class="bi bi-info-circle"></i>
                        Add <code>STATION_PHONE</code> and <code>STATION_ADDRESS</code> in your <code>.env</code> file.
                    </p>
                @endif
            </div>
        </div>
    </div>
</section>

<section class="section portal-section" id="portals">
    <div class="container">
        <h2 class="section-title text-center">Staff &amp; Business Portals</h2>
        <p class="section-sub text-center mx-auto">Authorized access for station management and tuck shop operations.</p>
        <div class="row g-4 justify-content-center">
            <div class="col-md-5">
                <div class="portal-card">
                    <div class="portal-card-header fuel">
                        <i class="bi bi-speedometer2 fs-2 mb-2 d-block"></i>
                        <h4 class="mb-1">Fuel Station Portal</h4>
                        <small style="opacity:0.9;">Management &amp; reporting system</small>
                    </div>
                    <div class="portal-card-body">
                        <p>
                            Login to manage tanks, shifts, sales, expenses, stock, dip readings,
                            owner fuel usage, and business reports.
                        </p>
                        <a href="{{ route('login') }}" class="btn-portal btn-portal-fuel">
                            <i class="bi bi-box-arrow-in-right"></i> Open Fuel Station Portal
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="portal-card">
                    <div class="portal-card-header shop">
                        <i class="bi bi-shop fs-2 mb-2 d-block"></i>
                        <h4 class="mb-1">Tuck Shop Portal</h4>
                        <small style="opacity:0.9;">Point of sale &amp; inventory</small>
                    </div>
                    <div class="portal-card-body">
                        <p>
                            Access the tuck shop POS system for sales, stock, and daily shop operations.
                            URL can be updated in configuration when your POS is ready.
                        </p>
                        <a href="{{ $tuckShopUrl }}" target="_blank" rel="noopener noreferrer" class="btn-portal btn-portal-shop">
                            <i class="bi bi-box-arrow-up-right"></i> Open Tuck Shop Portal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<footer class="landing-footer">
    <div class="container text-center">
        <p class="mb-1"><strong>{{ $stationName }}</strong> · {{ $brand }} Authorized Dealer</p>
        <p class="mb-0 small opacity-75">&copy; {{ date('Y') }} {{ $stationName }}. All rights reserved.</p>
    </div>
</footer>

</body>
</html>
