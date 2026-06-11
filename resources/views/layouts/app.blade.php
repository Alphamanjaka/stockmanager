<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockMaster - @yield('title')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }

        .sidebar {
            min-height: 100vh;
            background: #212529;
            color: white;
        }

        .sidebar a {
            color: #adb5bd;
            text-decoration: none;
            display: block;
            padding: 10px 20px;
        }

        .sidebar a:hover {
            background: #343a40;
            color: white;
        }

        .active-link {
            background: #0d6efd !important;
            color: white !important;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-2 d-none d-md-block sidebar p-0">
                <div class="p-3">
                    <h4 class="text-white"><i class="bi bi-box-seam"></i> StockMaster</h4>
                </div>
                <hr>
                <a href="/dashboard" class="{{ request()->is('/dashboard') ? 'active-link' : '' }}">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </nav>

            <main class="col-md-10 ms-sm-auto px-md-4">
                <div
                    class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">@yield('title')</h1>

                    <!-- Zone de notifications ajoutée ici -->
                    @auth
                        @php $notifications = auth()->user()->unreadNotifications; @endphp
                        <ul class="nav">
                            <li class="nav-item dropdown">
                                <a class="nav-link text-dark position-relative" href="#" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    <i class="bi bi-bell fs-5"></i>
                                    @if ($notifications->count() > 0)
                                        <span
                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            {{ $notifications->count() }}
                                            <span class="visually-hidden">notifications</span>
                                        </span>
                                    @endif
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width: 250px;">
                                    @forelse($notifications as $notification)
                                        <li>
                                            <a class="dropdown-item"
                                                href="{{ route('products.index', ['search' => $notification->data['product_name'] ?? '']) }}">
                                                <strong class="text-danger"><i class="bi bi-exclamation-circle"></i> Warning!</strong><br>
                                                {{ $notification->data['product_name'] ?? 'Product' }}
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
                                        <li><span class="dropdown-item text-muted">No alerts</span></li>
                                    @endforelse
                                </ul>
                            </li>
                        </ul>
                    @endauth
                </div>

                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
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
