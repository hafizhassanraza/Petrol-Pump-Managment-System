<div class="sidebar">

    <div class="mt-4 text-center">
        <a href="{{ url('/') }}">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" style="max-width:140px; max-height:60px; object-fit:contain;">
        </a>
    </div>


    <a href="{{ url('/') }}" class="{{ request()->is('/') ? 'active' : '' }}">
        <i class="bi bi-speedometer2"></i>
        Dashboard
    </a>


    <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') ? 'active' : '' }}">
        <i class="bi bi-droplet"></i>
        Products
    </a>


    <a href="{{ route('tanks.index') }}" class="{{ request()->routeIs('tanks.*') ? 'active' : '' }}">
        <i class="bi bi-database"></i>
        Tanks
    </a>


    <a href="{{ route('dispensers.index') }}" class="{{ request()->routeIs('dispensers.*') ? 'active' : '' }}">
        <i class="bi bi-hdd-stack"></i>
        Dispensers
    </a>


    <a href="{{ route('nozzles.index') }}" class="{{ request()->routeIs('nozzles.*') ? 'active' : '' }}">
        <i class="bi bi-funnel"></i>
        Nozzles
    </a>


    <a href="{{ route('employees.index') }}" class="{{ request()->routeIs('employees.*') ? 'active' : '' }}">
        <i class="bi bi-people"></i>
        Employees
    </a>


    <a href="{{ route('employee-shifts.index') }}" class="{{ request()->routeIs('employee-shifts.*') ? 'active' : '' }}">
        <i class="bi bi-arrow-repeat"></i>
        Employee Shifts
    </a>


    <a href="{{ route('tank-refills.index') }}" class="{{ request()->routeIs('tank-refills.*') ? 'active' : '' }}">
        <i class="bi bi-fuel-pump"></i>
        Tank Refills
    </a>

    <a href="{{ route('tank-dip-readings.index') }}" class="{{ request()->routeIs('tank-dip-readings.*') ? 'active' : '' }}">
        <i class="bi bi-clipboard-data"></i>
        Tank Dip Readings
    </a>

    <a href="{{ route('owner-fuel-usages.index') }}" class="{{ request()->routeIs('owner-fuel-usages.*') ? 'active' : '' }}">
        <i class="bi bi-person-badge"></i>
        Owner Fuel Usage
    </a>

    <a href="{{ route('expenses.index') }}" class="{{ request()->routeIs('expenses.*') ? 'active' : '' }}">
        <i class="bi bi-cash-stack"></i>
        Expenses
    </a>


    {{-- <a href="#">
        <i class="bi bi-truck"></i>
        Credit Customers
    </a> --}}


    <li>
        <a href="{{ route('reports.dashboard') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}">
            📊 Reports
        </a>
    </li>


    {{-- <a href="#">
        <i class="bi bi-gear"></i>
        Settings
    </a> --}}

</div>