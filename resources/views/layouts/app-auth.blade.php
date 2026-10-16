<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@hasSection('title')@yield('title') · @endif StockMaster</title>
    @include('layouts.partials.theme')
    @vite(['resources/css/custom.css', 'resources/css/auth.css'])
</head>

{{-- Pages hors application (connexion, choix du profil) : panneau de marque à gauche, contenu à droite. --}}

<body class="auth">
    <aside class="auth-brand">
        <div class="ui-brand">
            <span class="ui-brand-mark"><i class="bi bi-box-seam-fill" aria-hidden="true"></i></span>
            <span class="ui-brand-name">StockMaster</span>
        </div>
        <div class="auth-brand-body">
            <h2>Inventory, purchasing and sales in one place.</h2>
            <ul>
                <li><i class="bi bi-box-seam" aria-hidden="true"></i> Real-time stock and alerts</li>
                <li><i class="bi bi-cart3" aria-hidden="true"></i> Purchases and supplier follow-up</li>
                <li><i class="bi bi-shop" aria-hidden="true"></i> Point of sale and invoices</li>
            </ul>
        </div>
        <small>© {{ date('Y') }} StockMaster</small>
    </aside>

    <main class="auth-main">
        <div class="auth-panel">
            @yield('content')
        </div>
    </main>

    @stack('scripts')
</body>

</html>
