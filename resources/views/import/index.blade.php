@extends('layouts.app-back-office')

@section('content')
    <div class="container">
        <h2 class="mb-4">Centre d'Importation de Données</h2>

        <div class="row">
            @php
                $modules = [
                    [
                        'id' => 'categories',
                        'name' => 'Catégories',
                        'icon' => 'fa-tags',
                        'desc' => 'Importez vos familles de produits.',
                    ],
                    [
                        'id' => 'suppliers',
                        'name' => 'Fournisseurs',
                        'icon' => 'fa-truck',
                        'desc' => 'Base de données de vos partenaires.',
                    ],
                    [
                        'id' => 'products',
                        'name' => 'Produits',
                        'icon' => 'fa-box',
                        'desc' => 'Stock principal et prix.',
                    ],
                    [
                        'id' => 'purchases',
                        'name' => 'Achats',
                        'icon' => 'fa-shopping-cart',
                        'desc' => 'Historique des commandes fournisseurs.',
                    ],
                    [
                        'id'=>'colors',
                        'name'=>'Couleurs',
                        'icon'=>'fa-palette',
                        'desc'=>'Gérez les différentes couleurs de vos produits.',
                    ]
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
                                    <button type="submit" class="btn btn-primary btn-sm">Importer</button>
                                    <a href="{{ route('admin.imports.template', $module['id']) }}"
                                        class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-download"></i> Modèle CSV
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
