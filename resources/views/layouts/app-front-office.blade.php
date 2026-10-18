<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') · @endif StockMaster</title>
    @include('layouts.partials.theme')
    @vite(['resources/css/custom.css', 'resources/css/front.css', 'resources/js/app.js'])
    @stack('css')
</head>

{{-- Squelette du front office (vendeurs). Sections : title, actions, content ; piles : css, scripts. --}}

@php
    $links = [
        ['route' => 'saler.dashboard', 'active' => 'saler.dashboard', 'icon' => 'bi-house', 'label' => 'Home'],
        ['route' => 'saler.products.index', 'active' => 'saler.products.*', 'icon' => 'bi-box', 'label' => 'Products'],
        ['route' => 'saler.index', 'active' => ['saler.index', 'saler.show'], 'icon' => 'bi-receipt', 'label' => 'Sales'],
        ['route' => 'saler.create', 'active' => 'saler.create', 'icon' => 'bi-plus-circle', 'label' => 'New Sale'],
    ];
@endphp

<body class="fo" data-session-success="{{ session('success') }}" data-session-error="{{ session('error') }}"
    data-i18n-cancel="{{ __('backoffice.cancel') }}" data-i18n-confirm="{{ __('backoffice.confirm') }}">

    <header class="fo-topbar">
        <a class="ui-brand" href="{{ route('saler.dashboard') }}">
            <span class="ui-brand-mark"><i class="bi bi-box-seam-fill" aria-hidden="true"></i></span>
            <span class="ui-brand-name">StockMaster</span>
        </a>

        <ul class="fo-nav">
            @foreach ($links as $link)
                @php $active = request()->routeIs(...(array) $link['active']); @endphp
                <li>
                    <a @class(['fo-nav-link', 'is-active' => $active]) href="{{ route($link['route']) }}"
                        @if ($active) aria-current="page" @endif title="{{ $link['label'] }}">
                        <i class="bi {{ $link['icon'] }}" aria-hidden="true"></i> <span>{{ $link['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="fo-tools">
            <div class="dropdown">
                <button class="fo-user dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="ui-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                    <span class="d-none d-md-inline">{{ auth()->user()->name }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text small text-muted">{{ auth()->user()->email }}</span></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button class="dropdown-item text-danger" type="submit">
                                <i class="bi bi-box-arrow-right" aria-hidden="true"></i> {{ __('backoffice.logout') }}
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <div class="fo-pagebar">
        <h1 class="fo-page-title">@yield('title')</h1>
        @hasSection('actions')
            <div class="fo-actions">@yield('actions')</div>
        @endif
    </div>

    <main class="fo-main">
        @yield('content')
    </main>

    @stack('scripts')
</body>

</html>
