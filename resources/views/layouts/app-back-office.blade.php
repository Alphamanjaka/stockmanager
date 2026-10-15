<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>StockMaster - Back Office @yield('title')</title>
    @include('layouts.partials.theme')
    @vite(['resources/css/custom.css', 'resources/css/back-office.css', 'resources/js/app.js', 'resources/js/back.js'])
    @stack('css')
    @yield('styles')
</head>

{{--
    Squelette du back office. Le menu ($nav) est résolu par App\Core\Navigation\BackOfficeMenu
    à partir des déclarations des modules ($menuSpaces, $menu) : rien n'est écrit en dur ici.
    Sections disponibles pour les pages : title, actions, content, styles ; piles : css, scripts.
--}}

<body class="bo" data-session-success="{{ session('success') }}" data-session-error="{{ session('error') }}">

    {{-- 1. Barre du haut : lanceur de modules, module courant et son menu, outils --}}
    <header class="bo-topbar">
        @include('layouts.back-office.launcher')

        @if ($nav->current)
            <a class="bo-space" href="{{ $nav->current->url }}">
                <i class="fas {{ $nav->current->icon }} fa-fw" aria-hidden="true"></i>
                <span>{{ $nav->current->label }}</span>
            </a>
        @else
            <a class="bo-space" href="{{ route('admin.dashboard') }}">
                <i class="fas fa-cube fa-fw" aria-hidden="true"></i>
                <span>StockMaster</span>
            </a>
        @endif

        @include('layouts.back-office.module-nav')

        @include('layouts.back-office.tools')
    </header>

    {{-- 2. Barre de page : fil d'Ariane et actions propres à la page.
         Un fil d'un seul niveau répéterait le nom du module affiché dans la barre du haut : il est omis. --}}
    @php $breadcrumb = count($nav->breadcrumb) > 1 ? $nav->breadcrumb : []; @endphp
    @if ($breadcrumb || View::hasSection('actions'))
        <div class="bo-pagebar">
            <nav aria-label="{{ __('backoffice.breadcrumb') }}">
                <ol class="breadcrumb">
                    @foreach ($breadcrumb as $crumb)
                        @if ($crumb['url'])
                            <li class="breadcrumb-item"><a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a></li>
                        @else
                            <li class="breadcrumb-item active" aria-current="page">{{ $crumb['label'] }}</li>
                        @endif
                    @endforeach
                </ol>
            </nav>
            <div class="bo-actions">
                @yield('actions')
            </div>
        </div>
    @endif

    {{-- 3. Contenu de la page, pleine largeur --}}
    <main class="bo-main">
        @yield('content')
    </main>

    {{-- 4. Pied de page --}}
    <footer class="bo-footer">
        <a href="https://wa.me/0346258154" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
        <a href="mailto:alphamanjaka@gmail.com" aria-label="E-mail"><i class="fas fa-envelope"></i></a>
        <a href="https://alphamanjaka.github.io/porfolio/" target="_blank" rel="noopener" aria-label="Portfolio"><i class="fas fa-globe"></i></a>
        <a href="https://github.com/alphamanjaka/" target="_blank" rel="noopener" aria-label="GitHub"><i class="fab fa-github"></i></a>
        <span>© 2025{{ date('Y') > 2025 ? '-' . date('Y') : '' }} Alpha Manjaka. {{ __('backoffice.footer_rights') }}</span>
    </footer>

    {{-- Raccourcis Alt + touche → URL, lus par back.js --}}
    <script type="application/json" id="bo-shortcuts">@json((object) $nav->shortcuts)</script>
    @stack('scripts')
</body>

</html>
