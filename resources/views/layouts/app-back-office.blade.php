<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockMaster - Back Office @yield('title')</title>
    {{-- Load assets specific to the back office --}}
    @vite(['resources/css/custom.css', 'resources/css/sidebar.css', 'resources/js/app.js', 'resources/js/back.js'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @stack('css')
</head>

<body data-session-success="{{ session('success') }}" data-session-error="{{ session('error') }}">

    <nav class="sidebar p-0">
        <div class="p-3 mb-2">
            <h4 class="mb-0"><i class="fas fa-cube text-primary me-2"></i>StockMaster</h4>
            <small class="text-muted ms-4">{{ __('backoffice.administration') }}</small>
        </div>
        <div class="grow overflow-auto">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}"
                        class="{{ request()->is('admin/dashboard*') ? 'active-link' : '' }}" data-shortcut="d"
                        title="Dashboard (Alt + D)">
                        <span><i class="fas fa-tachometer-alt fa-fw me-2"></i> <span
                                class="sidebar-text">{{ __('backoffice.dashboard') }}</span></span>
                        <span class="shortcut-badge">Alt+D</span>
                    </a>
                </li>

                <li class="sidebar-header"><span class="sidebar-text">{{ __('backoffice.management') }}</span></li>

                <li class="nav-item menu-group">
                    {{-- default collapse state closed --}}
                    <a class="d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                        title="{{ __('backoffice.inventory') }}" href="#stockSubmenu" role="button"
                        aria-expanded="false">
                        <span><i class="fas fa-warehouse fa-fw me-2"></i> <span
                                class="sidebar-text">{{ __('backoffice.inventory') }}</span></span>
                        <i class="fas fa-chevron-down fa-xs sidebar-text"></i>
                    </a>
                    <div class="collapse hide" id="stockSubmenu">
                        <a href="{{ route('admin.products.index') }}"
                            class="{{ request()->is('admin/products*') ? 'text-primary fw-bold' : '' }}"
                            data-shortcut="p" title="{{ __('backoffice.products') }} (Alt + P)">
                            {{-- icon representation if sidebar collapsed --}}
                            <span><i class="fas fa-box fa-fw me-2"></i></span>
                            <span class="sidebar-text">{{ __('backoffice.products') }}</span> <span
                                class="shortcut-badge">Alt+P</span>
                        </a>
                        <a href="{{ route('admin.categories.index') }}"
                            class="{{ request()->is('admin/categories*') ? 'text-primary fw-bold' : '' }}"
                            data-shortcut="c" title="{{ __('backoffice.categories') }} (Alt + C)">
                            <span><i class="fas fa-list fa-fw me-2"></i></span>
                            <span class="sidebar-text">{{ __('backoffice.categories') }}</span> <span
                                class="shortcut-badge">Alt+C</span>
                        </a>
                        <a href="{{ route('admin.colors.index') }}"
                            class="{{ request()->is('admin/colors*') ? 'active-link' : '' }}" data-shortcut="c">
                            <span><i class="fas fa-palette fa-fw me-2"></i></span>
                            <span class="sidebar-text">{{ __('backoffice.colors') }}</span>
                            <span class="shortcut-badge">Alt+C</span>
                        </a>
                        <a href="{{ route('admin.movements.index') }}"
                            class="{{ request()->is('admin/movements*') ? 'text-primary fw-bold' : '' }}"
                            data-shortcut="m" title="{{ __('backoffice.movements') }} (Alt + M)">
                            <span><i class="fas fa-shipping-fast fa-fw me-2"></i></span>
                            <span class="sidebar-text">{{ __('backoffice.movements') }}</span> <span
                                class="shortcut-badge">Alt+M</span>
                        </a>
                    </div>
                </li>

                <li class="sidebar-header"><span class="sidebar-text">Commerce</span></li>

                <li class="nav-item menu-group">
                    <a class="d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                        title="{{ __('backoffice.purchases_sales') }}" href="#commerceSubmenu" role="button"
                        aria-expanded="false">
                        <span><i class="fas fa-exchange-alt fa-fw me-2"></i> <span
                                class="sidebar-text">{{ __('backoffice.purchases_sales') }}</span></span>
                        <i class="fas fa-chevron-down fa-xs sidebar-text"></i>
                    </a>
                    <div class="collapse hide" id="commerceSubmenu">
                        <a href="{{ route('admin.suppliers.index') }}"
                            class="{{ request()->is('admin/suppliers*') ? 'text-primary fw-bold' : '' }}"
                            data-shortcut="f">
                            <span><i class="fas fa-truck fa-fw me-2"></i></span>
                            <span class="sidebar-text">{{ __('backoffice.suppliers') }}</span> <span
                                class="shortcut-badge">Alt+F</span>
                        </a>
                        {{--  Sales link --}}
                        <a href="{{ route('admin.sales.index') }}"
                            class="{{ request()->is('admin/sales*') ? 'text-primary fw-bold' : '' }}" data-shortcut="s"
                            title="{{ __('backoffice.sales') }} (Alt + S)">
                            <span><i class="fas fa-shopping-cart fa-fw me-2"></i></span>
                            <span class="sidebar-text">{{ __('backoffice.sales') }}</span> <span
                                class="shortcut-badge">Alt+S</span>
                        </a>
                        <a href="{{ route('admin.purchases.index') }}"
                            class="{{ request()->is('admin/purchases*') ? 'text-primary fw-bold' : '' }}"
                            data-shortcut="a" title="{{ __('backoffice.purchases') }} (Alt + A)">
                            <span><i class="fas fa-receipt fa-fw me-2"></i></span>
                            <span class="sidebar-text">{{ __('backoffice.purchases') }}</span> <span
                                class="shortcut-badge">Alt+A</span>
                        </a>
                    </div>
                </li>
                <li class="sidebar-header"><span class="sidebar-text">{{ __('backoffice.users') }}</span></li>
                <li class="nav-item">
                    <a href="{{ route('admin.users.index') }}"
                        class="{{ request()->is('admin/users*') ? 'text-primary fw-bold' : '' }}" data-shortcut="u"
                        title="{{ __('backoffice.users') }} (Alt + U)">
                        <span><i class="fas fa-users fa-fw me-2"></i> <span
                                class="sidebar-text">{{ __('backoffice.users') }}</span></span>
                        <span class="shortcut-badge">Alt+U</span>
                    </a>
                </li>
            </ul>
        </div>


        <div class="mt-auto border-top border-secondary pt-2">
            <a href="{{ route('admin.imports.index') }}"
                class="{{ request()->is('admin/imports*') ? 'active-link' : '' }}" data-shortcut="i"
                title="{{ __('backoffice.import') }} (Alt + I)">
                <i class="fas fa-file-import"></i>
                <span><span class="sidebar-text">{{ __('backoffice.import') }}</span></span>
                <span class="shortcut-badge">Alt+I</span>
            </a>

            <a href="{{ route('admin.settings.index') }}"
                class="{{ request()->is('admin/settings*') ? 'active-link' : '' }}" data-shortcut="s"
                title="{{ __('backoffice.settings') }} (Alt + S)">
                <span><i class="fas fa-cog fa-fw me-2"></i> <span
                        class="sidebar-text">{{ __('backoffice.settings') }}</span></span>
                <span class="shortcut-badge">Alt+S</span>
            </a>
        </div>
    </nav>

    <main class="px-md-4 bg-light min-vh-100">

        {{-- 1. Top Navbar : Navigation globale et outils --}}
        <div
            class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom bg-white shadow-sm rounded px-3">
            <div class="d-flex align-items-center">
                <button id="toggleSidebar" class="btn btn-link text-secondary shadow-none me-3">
                    <i class="fas fa-align-left"></i>
                </button>
                <div class="dropdown search-input-wrapper">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search text-secondary"></i>
                        </span>
                        <input type="search" id="global-search-input" class="form-control border-start-0"
                            placeholder="{{ __('backoffice.search_placeholder') }}" aria-label="Search"
                            autocomplete="off" data-bs-toggle="dropdown" aria-expanded="false" />
                    </div>
                    <div id="global-search-results" class="dropdown-menu">
                        <!-- Search results will be inserted here by JavaScript -->
                    </div>
                </div>

            </div>

            {{-- User tools (Right) --}}
            <div class="d-flex align-items-center gap-3">
                {{-- language --}}
                {{-- Language Selector --}}
                <div class="dropdown">
                    <a class="text-dark dropdown-toggle text-decoration-none d-flex align-items-center" href="#"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        @switch(app()->getLocale())
                            @case('fr')
                                🇫🇷 <span class="ms-1 d-none d-sm-inline">FR</span>
                            @break

                            @case('es')
                                🇪🇸 <span class="ms-1 d-none d-sm-inline">ES</span>
                            @break

                            @default
                                🇺🇸 <span class="ms-1 d-none d-sm-inline">EN</span>
                        @endswitch
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="min-width: 120px;">
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 {{ app()->getLocale() === 'en' ? 'active bg-light text-dark fw-bold' : '' }}"
                                href="{{ route('lang.switch', 'en') }}">
                                🇺🇸 English
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 {{ app()->getLocale() === 'fr' ? 'active bg-light text-dark fw-bold' : '' }}"
                                href="{{ route('lang.switch', 'fr') }}">
                                🇫🇷 Français
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 {{ app()->getLocale() === 'es' ? 'active bg-light text-dark fw-bold' : '' }}"
                                href="{{ route('lang.switch', 'es') }}">
                                🇪🇸 Español
                            </a>
                        </li>
                    </ul>
                </div>

                @auth
                    {{-- Theme Toggle --}}
                    <button id="theme-toggle" class="btn btn-link text-dark shadow-none p-0" aria-pressed="false"
                        aria-label="Toggle theme" title="Change theme">
                        <i id="theme-icon" class="fas fa-moon" aria-hidden="true"></i>
                    </button>

                    {{-- Notifications --}}
                    @php $notifications = auth()->user()->unreadNotifications; @endphp
                    <div class="dropdown">
                        <a class="text-dark position-relative" href="#" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            @if ($notifications->count() > 0)
                                <span
                                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                    style="font-size: 0.6rem;">
                                    {{ $notifications->count() }}
                                </span>
                            @endif
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="min-width: 280px;">
                            <li class="dropdown-header">{{ __('backoffice.notifications') }}</li>
                            @forelse($notifications as $notification)
                                <li>
                                    <a class="dropdown-item d-flex align-items-start gap-2 py-2"
                                        href="{{ route('admin.products.index', ['search' => $notification->data['product_name'] ?? '']) }}">
                                        <div class="text-primary mt-1"><i class="fas fa-exclamation-circle"></i>
                                        </div>
                                        <div>
                                            <strong
                                                class="d-block text-dark">{{ $notification->data['product_name'] ?? 'Product' }}</strong>
                                            <small class="text-muted">{{ __('backoffice.stock_alert') }}:
                                                {{ $notification->data['current_stock'] ?? 0 }}
                                                {{ __('backoffice.stock_left') }}</small>
                                            {{-- <small class="text-muted">Critical stock alert: {{ $notification->data['current_stock'] ?? 0 }} remaining</small> --- IGNORE --- --}}
                                        </div>
                                    </a>
                                </li>
                                @if (!$loop->last)
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                @endif
                            @empty
                                <li><span class="dropdown-item text-muted small">
                                        {{ __('backoffice.no_notifications') }}</span></li>
                            @endforelse
                        </ul>
                    </div>

                    {{-- User profile --}}
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2"
                                style="width: 32px; height: 32px;">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                            <span class="d-none d-md-inline">{{ auth()->user()->name }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li><a class="dropdown-item"
                                    href="{{ route('admin.users.index') }}">{{ __('backoffice.profile') }}</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button class="dropdown-item text-danger"
                                        type="submit">{{ __('backoffice.logout') }}</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @endauth
            </div>
        </div>

        {{-- 2. Breadcrumb & Page title --}}
        <div class="d-flex justify-content-between align-items-center mb-4 px-2">
            <div>
                {{-- <h1 class="h3 mb-0 text-gray-800">@yield('title')</h1> --}}
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 small bg-transparent p-0">
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-primary">
                                <i class="fas fa-home"></i> {{ __('backoffice.admin') }}
                            </a>
                        </li>
                        {{-- Dynamic breadcrumb generation based on the URL --}}
                        @php
                            $segments = request()->segments();
                            $currentUrl = '';
                        @endphp
                        @foreach ($segments as $segment)
                            @php
                                $currentUrl .= '/' . $segment;
                                if ($segment === 'admin') {
                                    continue;
                                } // Already shown as root
                                if (is_numeric($segment)) {
                                    continue;
                                } // Ignore numeric IDs
                            @endphp
                            @if ($loop->last)
                                <li class="breadcrumb-item active text-muted" aria-current="page">
                                    {{ ucfirst($segment) }}</li>
                            @else
                                <li class="breadcrumb-item">
                                    <a href="{{ url($currentUrl) }}"
                                        class="text-decoration-none text-muted">{{ ucfirst($segment) }}</a>
                                </li>
                            @endif
                        @endforeach
                    </ol>
                </nav>
            </div>
            {{-- Area for page-specific action buttons (optional) --}}
            <div>
                @yield('actions')
            </div>
        </div>

        {{-- 3. Main Content --}}
        <div class="content-fluid">
            @yield('content')
        </div>
        <br />

    </main>

    {{-- 4. Footer text align end --}}
    <footer class="py-2 bg-light border-top text-end">
        <div class="container-fluid">
            {{-- Contact WhatsApp, mail, and portfolio --}}
            <a href="https://wa.me/0346258154" class="text-decoration-none me-3" target="_blank">
                <i class="fab fa-whatsapp fa-lg"></i>
            </a>
            <a href="mailto:alphamanjaka@gmail.com" class="text-decoration-none me-3">
                <i class="fas fa-envelope fa-lg"></i>
            </a>
            <a href="https://alphamanjaka.github.io/porfolio/" class="text-decoration-none me-3" target="_blank">
                <i class="fas fa-globe fa-lg"></i>
            </a>
            <a href="https://github.com/alphamanjaka/" class="text-decoration-none" target="_blank">
                <i class="fab fa-github fa-lg"></i>
            </a>

            @php
                $currentYear = date('Y');
                $startYear = 2025;
            @endphp
            <span class="text-muted ms-3">©
                {{ $startYear }}{{ $currentYear > $startYear ? '-' . $currentYear : '' }} Alpha Manjaka.
                {{ __('backoffice.footer_rights') }}</span>
        </div>
    </footer>
    @stack('scripts')
</body>

</html>
