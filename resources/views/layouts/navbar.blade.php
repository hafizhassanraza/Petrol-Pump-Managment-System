<header class="topbar">
    <h1 class="topbar-title">{{ $pageTitle ?? 'Fuel Station' }}</h1>
    <div class="d-flex align-items-center gap-3">
        <span class="text-muted small d-none d-md-inline">
            <i class="bi bi-person-circle"></i>
            {{ auth()->user()->name ?? 'User' }}
        </span>
        <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-box-arrow-right"></i> Logout
            </button>
        </form>
    </div>
</header>
