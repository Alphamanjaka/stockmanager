@extends('layouts.app-auth')

@section('title', __('Profile selection'))

@section('content')
    <h1 class="auth-title">{{ __('Welcome, :name', ['name' => auth()->user()->name]) }}</h1>
    <p class="auth-subtitle">{{ __('Select your working profile.') }}</p>

    @if ($message = Session::get('error'))
        <x-ui.alert tone="danger">{{ $message }}</x-ui.alert>
    @endif

    @if ($canAccessFrontOffice)
        <a href="{{ route('saler.dashboard') }}" class="auth-choice">
            <span class="ui-stat-icon ui-tone-primary"><i class="bi bi-shop" aria-hidden="true"></i></span>
            <span>
                <strong>{{ __('Front office') }}</strong>
                <small>{{ __('Sales and point of sale · Seller') }}</small>
            </span>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
        </a>
    @endif

    @if ($canAccessBackOffice)
        <a href="{{ route('admin.dashboard') }}" class="auth-choice">
            <span class="ui-stat-icon ui-tone-info"><i class="bi bi-building-gear" aria-hidden="true"></i></span>
            <span>
                <strong>{{ __('Back office') }}</strong>
                <small>{{ __('Products, stock and purchases · Administrator') }}</small>
            </span>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
        </a>
    @endif

    @if (!$canAccessFrontOffice && !$canAccessBackOffice)
        <x-ui.alert tone="warning" :title="__('Limited access')">
            {{ __('Your profile has no module access. Please contact an administrator.') }}
        </x-ui.alert>
    @endif
@endsection
