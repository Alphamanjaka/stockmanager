<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockMaster - Selling @yield('title')</title>
    {{-- Chargement des assets spécifiques au Front-Office --}}
    @vite(['resources/css/custom.css', 'resources/css/front.css', 'resources/js/app.js', 'resources/js/front.js'])
    @stack('css')
</head>

<body data-session-success="{{ session('success') }}" data-session-error="{{ session('error') }}">

    <!-- Navbar simplifié -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand text-white fw-bold" href="{{ route('sales.dashboard') }}">
                <i class="fas fa-shopping-cart"></i> Front Office - Selling Management
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('sales/dashboard*') ? 'active-link' : '' }}"
                            href="{{ route('sales.dashboard') }}">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('sales') ? 'active-link' : '' }}"
                            href="{{ route('sales.index') }}">
                            <i class="fas fa-receipt"></i> Sales
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('sales/create') ? 'active-link' : '' }}"
                            href="{{ route('sales.create') }}">
                            <i class="fas fa-plus-circle"></i> New Sale
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> {{ auth()->user()->name }}
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('logout') }}"
                                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <main>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">@yield('title')</h1>
            </div>
            <div class="content">
                @yield('content')
            </div>
        </main>
    </div>

    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>
    @stack('scripts')
</body>

</html>
