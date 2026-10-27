<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') · @endif StockMaster</title>
    @include('layouts.partials.theme')
    @vite(['resources/css/custom.css', 'resources/css/back-office.css', 'resources/js/app.js', 'resources/js/back.js'])
    @stack('css')
    @yield('styles')
</head>

{{--
    Squelette du back office. Le menu ($nav) est résolu par App\Core\Navigation\BackOfficeMenu
    à partir des déclarations des modules ($menuSpaces, $menu) : rien n'est écrit en dur ici.
    Sections disponibles pour les pages : title, subtitle, actions, content, styles ; piles : css, scripts.
    Les éléments d'interface communs sont des composants Blade : resources/views/components/ui/ (<x-ui.card>...).
--}}

<body class="bo" data-session-success="{{ session('success') }}" data-session-error="{{ session('error') }}"
    data-i18n-cancel="{{ __('backoffice.cancel') }}" data-i18n-confirm="{{ __('backoffice.confirm') }}">

    {{-- 1. En-tête : barre d'application (lanceur, module courant, outils), puis menu du module --}}
    <header class="bo-header">
        <div class="bo-topbar">
            @include('layouts.back-office.launcher')

            <a class="ui-brand" href="{{ route(\App\Core\Support\Role::landingRoute(auth()->user()?->role)) }}">
                <span class="ui-brand-mark"><i class="bi bi-box-seam-fill" aria-hidden="true"></i></span>
                <span class="ui-brand-name">StockMaster</span>
            </a>

            @if ($nav->current)
                <a class="bo-space" href="{{ $nav->current->url }}">
                    <i class="bi {{ $nav->current->icon }}" aria-hidden="true"></i>
                    <span>{{ $nav->current->label }}</span>
                </a>
            @endif

            @include('layouts.back-office.tools')
        </div>

        @include('layouts.back-office.module-nav')
    </header>

    {{-- 2. En-tête de page : fil d'Ariane, titre et actions propres à la page.
         Un fil d'un seul niveau répéterait le nom du module affiché dans la barre du haut : il est omis. --}}
    @php $breadcrumb = count($nav->breadcrumb) > 1 ? $nav->breadcrumb : []; @endphp
    <div class="bo-pagebar">
        <div class="bo-page-heading">
            @if ($breadcrumb)
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
            @endif
            <h1 class="bo-page-title">
                @hasSection('title')
                    @yield('title')
                @else
                    {{ $nav->current->label ?? 'StockMaster' }}
                @endif
            </h1>
            @hasSection('subtitle')
                <p class="bo-page-subtitle">@yield('subtitle')</p>
            @endif
        </div>
        @hasSection('actions')
            <div class="bo-actions">
                @yield('actions')
            </div>
        @endif
    </div>

    {{-- 3. Contenu de la page, pleine largeur --}}
    <main class="bo-main">
        @yield('content')
    </main>

    {{-- 4. Pied de page --}}
    <footer class="bo-footer">
        <a href="https://wa.me/0346258154" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
        <a href="mailto:alphamanjaka@gmail.com" aria-label="E-mail"><i class="bi bi-envelope"></i></a>
        <a href="https://alphamanjaka.github.io/porfolio/" target="_blank" rel="noopener" aria-label="Portfolio"><i class="bi bi-globe2"></i></a>
        <a href="https://github.com/alphamanjaka/" target="_blank" rel="noopener" aria-label="GitHub"><i class="bi bi-github"></i></a>
        <span>© 2025{{ date('Y') > 2025 ? '-' . date('Y') : '' }} Alpha Manjaka. {{ __('backoffice.footer_rights') }}</span>
    </footer>

    {{-- Raccourcis Alt + touche → URL, lus par back.js --}}
    <script type="application/json" id="bo-shortcuts">@json((object) $nav->shortcuts)</script>
    @stack('scripts')
</body>

</html>
