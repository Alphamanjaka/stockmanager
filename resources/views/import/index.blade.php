@extends('layouts.app-back-office')

@section('content')
    <div class="container">
        <h2 class="mb-4">Data Import Center</h2>

        {{-- Affichage des messages de succès --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Affichage des messages d'avertissement --}}
        @if (session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Affichage des messages d'erreur et détails de validation --}}
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}

                @if (session('import_validation_errors'))
                    <hr>
                    <div class="small">
                        <strong>Détails des erreurs rencontrées :</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ((array) session('import_validation_errors') as $failure)
                                <li>
                                    Ligne <strong>{{ $failure['row'] }}</strong> (champ
                                    <em>{{ $failure['attribute'] }}</em>) :
                                    {{ implode(', ', $failure['errors']) }}
                                    <span class="text-muted"> - Valeur lue :
                                        "{{ $failure['values'][$failure['attribute']] ?? 'N/A' }}"</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            @php
                $modules = [
                    [
                        'id' => 'categories',
                        'name' => 'Categories',
                        'icon' => 'fa-tags',
                        'desc' => 'Import your product categories to organize your inventory.',
                    ],
                    [
                        'id' => 'suppliers',
                        'name' => 'Suppliers',
                        'icon' => 'fa-truck',
                        'desc' => 'Import your suppliers to manage your procurement process.',
                    ],
                    [
                        'id' => 'products',
                        'name' => 'Products',
                        'icon' => 'fa-box',
                        'desc' => 'Import your products and manage their inventory.',
                    ],
                    [
                        'id' => 'purchases',
                        'name' => 'Purchases',
                        'icon' => 'fa-shopping-cart',
                        'desc' => 'Import your purchase records to keep track of your inventory inflow.',
                    ],
                    [
                        'id' => 'colors',
                        'name' => 'Colors',
                        'icon' => 'fa-palette',
                        'desc' => 'Manage the different colors of your products.',
                    ],
                ];
            @endphp

            @foreach ($modules as $module)
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas {{ $module['icon'] }} fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">{{ $module['name'] }}</h5>
                            <p class="card-text text-muted small">{{ $module['desc'] }}</p>

                            <form action="{{ route('admin.imports.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="type" value="{{ $module['id'] }}">
                                <div class="input-group input-group-sm mb-2">
                                    <input type="file" name="file" class="form-control" required>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm">Import</button>
                                    <a href="{{ route('admin.imports.template', $module['id']) }}"
                                        class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-download"></i> CSV Template
                                    </a>
                                </div>
                                {{-- Affichage des erreurs de validation spécifiques à ce formulaire --}}
                                @if ($errors->has('file') && old('type') === $module['id'])
                                    <div class="alert alert-danger mt-2">
                                        {{ $errors->first('file') }}
                                    </div>
                                @endif
                                @if ($errors->any() && old('type') === $module['id'])
                                    <div class="alert alert-danger mt-2">
                                        <ul>
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
