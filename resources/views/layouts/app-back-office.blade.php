<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockMaster - Back Office @yield('title')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }

        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #212529 0%, #1a1d20 100%);
            color: white;
            border-right: 3px solid #dc3545;
        }

        .sidebar a {
            color: #adb5bd;
            text-decoration: none;
            display: block;
            padding: 12px 20px;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .sidebar a:hover {
            background: #343a40;
            color: white;
            border-left: 3px solid #dc3545;
        }

        .active-link {
            background: #dc3545 !important;
            color: white !important;
            border-left: 3px solid #ff6b6b !important;
            font-weight: bold;
        }

        .sidebar h4 {
            color: #dc3545;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar hr {
            border-color: #dc3545;
            opacity: 0.3;
        }

        main {
            background-color: #f8f9fa;
            padding: 20px;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-2 d-none d-md-block sidebar p-0">
                <div class="p-3">
                    <h4><i class="fas fa-cogs"></i> StockMaster</h4>
                    <small class="text-danger">Back Office</small>
                </div>
                <hr>
                <a href="{{ route('admin.dashboard') }}"
                    class="{{ request()->is('admin/dashboard*') ? 'active-link' : '' }}">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="{{ route('admin.products.index') }}"
                    class="{{ request()->is('admin/products*') ? 'active-link' : '' }}">
                    <i class="fas fa-box"></i> Produits
                </a>
                <a href="{{ route('admin.categories.index') }}"
                    class="{{ request()->is('admin/categories*') ? 'active-link' : '' }}">
                    <i class="fas fa-list"></i> Catégories
                </a>
                <a href="{{ route('admin.suppliers.index') }}"
                    class="{{ request()->is('admin/suppliers*') ? 'active-link' : '' }}">
                    <i class="fas fa-truck"></i> Fournisseurs
                </a>
                <a href="{{ route('admin.movements.index') }}"
                    class="{{ request()->is('admin/movements*') ? 'active-link' : '' }}">
                    <i class="fas fa-arrows-alt"></i> Mouvements Stock
                </a>
                <a href="#" class="">
                    <i class="fas fa-chart-line"></i> Sales
                </a>
                <a href="{{ route('admin.purchases.index') }}"
                    class="{{ request()->is('admin/purchases*') ? 'active-link' : '' }}">
                    <i class="fas fa-shopping-basket"></i> Achats
                </a>
                <hr>
                <a href="{{ route('logout') }}"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                    style="color: #dc3545;">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </nav>

            <main class="col-md-10 ms-sm-auto px-md-4">
                <div
                    class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">@yield('title')</h1>

                    <!-- Zone de notifications -->
                    @auth
                        @php $notifications = auth()->user()->unreadNotifications; @endphp
                        <ul class="nav">
                            <li class="nav-item dropdown">
                                <a class="nav-link text-dark position-relative" href="#" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    <i class="fas fa-bell fs-5"></i>
                                    @if ($notifications->count() > 0)
                                        <span
                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            {{ $notifications->count() }}
                                        </span>
                                    @endif
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width: 250px;">
                                    @forelse($notifications as $notification)
                                        <li>
                                            <a class="dropdown-item"
                                                href="{{ route('products.index', ['search' => $notification->data['product_name'] ?? '']) }}">
                                                <strong class="text-danger"><i class="fas fa-exclamation-circle"></i>
                                                    Attention !</strong><br>
                                                {{ $notification->data['product_name'] ?? 'Produit' }}
                                                <small class="text-muted d-block">(Stock :
                                                    {{ $notification->data['current_stock'] ?? 0 }})</small>
                                            </a>
                                        </li>
                                        @if (!$loop->last)
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                        @endif
                                    @empty
                                        <li><span class="dropdown-item text-muted">Aucune alerte</span></li>
                                    @endforelse
                                </ul>
                            </li>
                            <li class="nav-item">
                                <span class="nav-link text-dark">{{ auth()->user()->name }}</span>
                            </li>
                        </ul>
                    @endauth
                </div>

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i>
                        {{ session('success') }}<button type="button" class="btn-close"
                            data-bs-dismiss="alert"></button></div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show"><i
                            class="fas fa-exclamation-circle"></i> {{ session('error') }}<button type="button"
                            class="btn-close" data-bs-dismiss="alert"></button></div>
                @endif

                <div class="content">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
