@extends('layouts.app-back-office')

@section('title', 'Settings')

@php
    $sections = [
        'general' => ['label' => 'Company', 'icon' => 'bi-building'],
        'regional' => ['label' => 'Regional & currency', 'icon' => 'bi-globe2'],
        'stock' => ['label' => 'Stock & products', 'icon' => 'bi-box-seam'],
        'interface' => ['label' => 'Interface & system', 'icon' => 'bi-sliders'],
    ];
@endphp

@section('content')
    <form id="settings-form" action="{{ route('admin.settings.update', ['setting' => 1]) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-md-4 col-lg-3">
                <x-ui.card flush>
                    <nav class="nav flex-column nav-pills p-2" role="tablist" aria-orientation="vertical">
                        @foreach ($sections as $key => $section)
                            <button @class(['nav-link text-start', 'active' => $loop->first]) id="settings-{{ $key }}-tab" data-bs-toggle="pill"
                                data-bs-target="#settings-{{ $key }}" type="button" role="tab" aria-controls="settings-{{ $key }}"
                                aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                <i class="bi {{ $section['icon'] }}" aria-hidden="true"></i> {{ $section['label'] }}
                            </button>
                        @endforeach
                    </nav>
                </x-ui.card>
            </div>

            <div class="col-md-8 col-lg-9">
                <div class="tab-content">
                    @foreach (['general' => 'general', 'regional' => 'regional', 'stock' => 'stock_products', 'interface' => 'interface'] as $key => $partial)
                        <div @class(['tab-pane fade', 'show active' => $loop->first]) id="settings-{{ $key }}" role="tabpanel"
                            aria-labelledby="settings-{{ $key }}-tab">
                            @include('core::settings.partials.' . $partial)
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="ui-form-bar ui-form-actions">
            <span class="small text-muted ui-form-actions-start">Settings apply to the whole application, for every user.</span>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> Save settings</button>
        </div>
    </form>
@endsection
