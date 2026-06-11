<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockMaster - Back Office @yield('title')</title>
    {{-- Chargement des assets spécifiques au Back-Office --}}
    @vite(['resources/css/custom.css', 'resources/css/sidebar.css', 'resources/js/app.js', 'resources/js/back.js'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @stack('css')
</head>

<body data-session-success="{{ session('success') }}" data-session-error="{{ session('error') }}">

    <nav class="sidebar p-0">
        <div class="p-3 mb-2">
            <h4 class="mb-0"><i class="fas fa-cube text-primary me-2"></i>StockMaster</h4>
            <small class="text-muted ms-4">Administration</small>
        </div>
        <div class="grow overflow-auto">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}"
                        class="{{ request()->is('admin/dashboard*') ? 'active-link' : '' }}" data-shortcut="d"
                        title="Dashboard (Alt + D)">
                        <span><i class="fas fa-tachometer-alt fa-fw me-2"></i> <span
                                class="sidebar-text">Dashboard</span></span>
                        <span class="shortcut-badge">Alt+D</span>
                    </a>
                </li>

                <li class="sidebar-header"><span class="sidebar-text">Management</span></li>

                <li class="nav-item menu-group">
                    {{-- collapse par defaut fermer --}}
                    <a class="d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                        title="Inventory" href="#stockSubmenu" role="button" aria-expanded="false">
                        <span><i class="fas fa-warehouse fa-fw me-2"></i> <span
                                class="sidebar-text">Inventory</span></span>
                        <i class="fas fa-chevron-down fa-xs sidebar-text"></i>
                    </a>
                    <div class="collapse hide" id="stockSubmenu">
                        <a href="{{ route('admin.products.index') }}"
                            class="{{ request()->is('admin/products*') ? 'text-primary fw-bold' : '' }}"
                            data-shortcut="p" title="Products (Alt + P)">
                            {{-- icon representation if sidebar collapsed --}}
                            <span><i class="fas fa-box fa-fw me-2"></i></span>
                            <span class="sidebar-text">Products</span> <span class="shortcut-badge">Alt+P</span>
                        </a>
                        <a href="{{ route('admin.categories.index') }}"
                            class="{{ request()->is('admin/categories*') ? 'text-primary fw-bold' : '' }}"
                            data-shortcut="c" title="Categories (Alt + C)">
                            <span><i class="fas fa-list fa-fw me-2"></i></span>
                            <span class="sidebar-text">Categories</span> <span class="shortcut-badge">Alt+C</span>
                        </a>
                        <a href="{{ route('admin.colors.index') }}"
                            class="{{ request()->is('admin/colors*') ? 'active-link' : '' }}" data-shortcut="c">
                            <span><i class="fas fa-palette fa-fw me-2"></i></span>
                            <span class="sidebar-text">Colors</span>
                            <span class="shortcut-badge">Alt+C</span>
                        </a>
                        <a href="{{ route('admin.movements.index') }}"
                            class="{{ request()->is('admin/movements*') ? 'text-primary fw-bold' : '' }}"
                            data-shortcut="m" title="Movements (Alt + M)">
                            <span><i class="fas fa-shipping-fast fa-fw me-2"></i></span>
                            <span class="sidebar-text">Movements</span> <span class="shortcut-badge">Alt+M</span>
                        </a>
                    </div>
                </li>

                <li class="sidebar-header"><span class="sidebar-text">Commerce</span></li>

                <li class="nav-item menu-group">
                    <a class="d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                        title="Purchases & Sales" href="#commerceSubmenu" role="button" aria-expanded="false">
                        <span><i class="fas fa-exchange-alt fa-fw me-2"></i> <span class="sidebar-text">Purchases &
                                Sales</span></span>
                        <i class="fas fa-chevron-down fa-xs sidebar-text"></i>
                    </a>
                    <div class="collapse hide" id="commerceSubmenu">
                        <a href="{{ route('admin.suppliers.index') }}"
                            class="{{ request()->is('admin/suppliers*') ? 'text-primary fw-bold' : '' }}"
                            data-shortcut="f">
                            <span><i class="fas fa-truck fa-fw me-2"></i></span>
                            <span class="sidebar-text">Suppliers</span> <span class="shortcut-badge">Alt+F</span>
                        </a>
                        {{--  Sales link --}}
                        <a href="{{ route('admin.sales.index') }}"
                            class="{{ request()->is('admin/sales*') ? 'text-primary fw-bold' : '' }}" data-shortcut="s"
                            title="Sales (Alt + S)">
                            <span><i class="fas fa-shopping-cart fa-fw me-2"></i></span>
                            <span class="sidebar-text">Sales</span> <span class="shortcut-badge">Alt+S</span>
                        </a>
                        <a href="{{ route('admin.purchases.index') }}"
                            class="{{ request()->is('admin/purchases*') ? 'text-primary fw-bold' : '' }}"
                            data-shortcut="a" title="Purchases (Alt + A)">
                            <span><i class="fas fa-receipt fa-fw me-2"></i></span>
                            <span class="sidebar-text">Purchases</span> <span class="shortcut-badge">Alt+A</span>
                        </a>
                    </div>
                </li>
                <li class="sidebar-header"><span class="sidebar-text">Users</span></li>
                <li class="nav-item">
                    <a href="{{ route('admin.users.index') }}"
                        class="{{ request()->is('admin/users*') ? 'text-primary fw-bold' : '' }}" data-shortcut="u"
                        title="Users (Alt + U)">
                        <span><i class="fas fa-users fa-fw me-2"></i> <span class="sidebar-text">Users</span></span>
                        <span class="shortcut-badge">Alt+U</span>
                    </a>
                </li>
            </ul>
        </div>


        <div class="mt-auto border-top border-secondary pt-2">
            <a href="{{ route('admin.imports.index') }}"
                class="{{ request()->is('admin/imports*') ? 'active-link' : '' }}" data-shortcut="i"
                title="Import (Alt + I)">
                <i class="fas fa-file-import"></i>
                <span><span class="sidebar-text">Import</span></span>
                <span class="shortcut-badge">Alt+I</span>
            </a>

            <a href="{{ route('admin.settings.index') }}"
                class="{{ request()->is('admin/settings*') ? 'active-link' : '' }}" data-shortcut="s"
                title="Settings (Alt + S)">
                <span><i class="fas fa-cog fa-fw me-2"></i> <span class="sidebar-text">Settings</span></span>
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
                            placeholder="Rechercher..." aria-label="Rechercher" autocomplete="off"
                            data-bs-toggle="dropdown" aria-expanded="false" />
                    </div>
                    <div id="global-search-results" class="dropdown-menu">
                        <!-- Les résultats de recherche seront insérés ici par JavaScript -->
                    </div>
                </div>

            </div>

            {{-- Outils Utilisateur (Droite) --}}
            <div class="d-flex align-items-center gap-3">
                @auth
                    {{-- Theme Toggle --}}
                    <button id="theme-toggle" class="btn btn-link text-dark shadow-none p-0" aria-pressed="false"
                        aria-label="Basculer le thème" title="Changer de thème">
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
                            <li class="dropdown-header">Notifications</li>
                            @forelse($notifications as $notification)
                                <li>
                                    <a class="dropdown-item d-flex align-items-start gap-2 py-2"
                                        href="{{ route('admin.products.index', ['search' => $notification->data['product_name'] ?? '']) }}">
                                        <div class="text-primary mt-1"><i class="fas fa-exclamation-circle"></i>
                                        </div>
                                        <div>
                                            <strong
                                                class="d-block text-dark">{{ $notification->data['product_name'] ?? 'Product' }}</strong>
                                            <small class="text-muted">Critical Stock Alert:
                                                {{ $notification->data['current_stock'] ?? 0 }} left</small>
                                            {{-- <small class="text-muted">Alerte de stock critique : {{ $notification->data['current_stock'] ?? 0 }} restants</small> --- IGNORE --- --}}
                                        </div>
                                    </a>
                                </li>
                                @if (!$loop->last)
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                @endif
                            @empty
                                <li><span class="dropdown-item text-muted small"> No notifications found.</span></li>
                            @endforelse
                        </ul>
                    </div>

                    {{-- Profil Utilisateur --}}
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
                            <li><a class="dropdown-item" href="{{ route('admin.users.index') }}">My Profile</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button class="dropdown-item text-danger" type="submit">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @endauth
            </div>
        </div>

        {{-- 2. Fil d'Ariane (Breadcrumb) & Titre de la page --}}
        <div class="d-flex justify-content-between align-items-center mb-4 px-2">
            <div>
                <h1 class="h3 mb-0 text-gray-800">@yield('title')</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 small bg-transparent p-0">
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-primary">
                                <i class="fas fa-home"></i> Admin
                            </a>
                        </li>
                        {{-- Génération dynamique du fil d'ariane basé sur l'URL --}}
                        @php
                            $segments = request()->segments();
                            $currentUrl = '';
                        @endphp
                        @foreach ($segments as $segment)
                            @php
                                $currentUrl .= '/' . $segment;
                                if ($segment === 'admin') {
                                    continue;
                                } // Déjà affiché comme racine
                                if (is_numeric($segment)) {
                                    continue;
                                } // Ignorer les ID numériques
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
            {{-- Zone pour des boutons d'action spécifiques à la page (optionnel) --}}
            <div>
                @yield('actions')
            </div>
        </div>

        {{-- 3. Contenu Principal --}}
        <div class="content-fluid">
            @yield('content')
        </div>
        <br />

    </main>

    {{-- 4. Pied de Page text align end --}}
    <footer class="py-2 bg-light border-top text-end">
        <div class="container-fluid">
            {{-- contact whatsApp and Mail and porfolio --}}
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
                {{ $startYear }}{{ $currentYear > $startYear ? '-' . $currentYear : '' }} Alpha Manjaka. All rights
                reserved.</span>
        </div>
    </footer>
    @stack('scripts')
</body>

</html>
