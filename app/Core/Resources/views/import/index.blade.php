@extends('layouts.app-back-office')

@section('title', __('Data import'))
@section('subtitle', __('Import CSV or Excel files. Download a template to see the expected columns.'))

@php
    $modules = [
        ['id' => 'categories', 'name' => __('Categories'), 'icon' => 'bi-diagram-3',
            'desc' => __('Import your product categories to organize your inventory.')],
        ['id' => 'suppliers', 'name' => __('Suppliers'), 'icon' => 'bi-truck',
            'desc' => __('Import your suppliers to manage your procurement process.')],
        ['id' => 'products', 'name' => __('Products'), 'icon' => 'bi-box',
            'desc' => __('One row per variant: name, category, brand, price, price_purchase, alert_stock, sku, barcode, and one option_<name> column per option (e.g. option_volume, option_color). Existing variants are updated (same SKU, same barcode, or same product + options + brand). No stock: it comes from received purchases.')],
        ['id' => 'purchases', 'name' => __('Purchases'), 'icon' => 'bi-cart3',
            'desc' => __('Import your purchase records. The stock increases when an imported purchase is marked as received.')],
        ['id' => 'colors', 'name' => __('Colors'), 'icon' => 'bi-palette',
            'desc' => __('Manage the different colors of your products.')],
    ];
@endphp

@section('content')
    <div class="ui-stack">
        @if (session('warning'))
            <x-ui.alert tone="warning">{{ session('warning') }}</x-ui.alert>
        @endif

        {{-- Détail des lignes refusées (le message d'erreur lui-même est affiché par le layout) --}}
        @if (session('import_validation_errors'))
            <x-ui.alert tone="danger" :title="__('Issues found during import')">
                <ul class="mb-0 ps-3">
                    @foreach ((array) session('import_validation_errors') as $failure)
                        <li>
                            {{ __('Row') }} <strong>{{ $failure['row'] }}</strong> ({{ __('field') }} <em>{{ $failure['attribute'] }}</em>) :
                            {{ implode(', ', $failure['errors']) }}
                            <span class="opacity-75"> — {{ __('read value:') }} "{{ $failure['values'][$failure['attribute']] ?? __('N/A') }}"</span>
                        </li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <div class="row g-3">
            @foreach ($modules as $module)
                <div class="col-md-6 col-xl-4">
                    <form action="{{ route('admin.imports.store') }}" method="POST" enctype="multipart/form-data" class="h-100">
                        @csrf
                        <input type="hidden" name="type" value="{{ $module['id'] }}">
                        <x-ui.card :title="$module['name']" :icon="$module['icon']" class="h-100" body-class="d-flex flex-column">
                            <p class="text-muted small flex-grow-1">{{ $module['desc'] }}</p>

                            <input type="file" name="file" required aria-label="{{ __(':name file', ['name' => $module['name']]) }}"
                                accept=".csv,.txt,.xlsx,.xls"
                                @class(['form-control', 'is-invalid' => old('type') === $module['id'] && $errors->any()])>
                            @if (old('type') === $module['id'] && $errors->any())
                                <div class="invalid-feedback d-block">
                                    @foreach ($errors->all() as $error)
                                        <div>{{ $error }}</div>
                                    @endforeach
                                </div>
                            @endif

                            <x-slot:footer>
                                <div class="ui-form-actions">
                                    <a href="{{ route('admin.imports.template', $module['id']) }}" class="btn btn-link ui-form-actions-start px-0">
                                        <i class="bi bi-download" aria-hidden="true"></i> {{ __('CSV template') }}
                                    </a>
                                    <button type="submit" class="btn btn-outline-secondary">
                                        <i class="bi bi-upload" aria-hidden="true"></i> {{ __('Import') }}
                                    </button>
                                </div>
                            </x-slot:footer>
                        </x-ui.card>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
@endsection
