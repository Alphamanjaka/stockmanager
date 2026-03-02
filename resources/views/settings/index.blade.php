@extends('layouts.app-back-office')

@section('title', '- Paramètres')

@section('content')
    <div class="container">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form action="{{ route('admin.settings.update', ['setting' => 1]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row">
                <!-- Sidebar Navigation (Vertical Tabs) -->
                <div class="col-md-3 mb-4">
                    <div class="nav flex-column nav-pills me-3" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                        <button class="nav-link active text-start" id="v-pills-general-tab" data-bs-toggle="pill"
                            data-bs-target="#v-pills-general" type="button" role="tab" aria-controls="v-pills-general"
                            aria-selected="true">
                            <i class="fas fa-building me-2"></i> Entreprise
                        </button>
                        <button class="nav-link text-start" id="v-pills-regional-tab" data-bs-toggle="pill"
                            data-bs-target="#v-pills-regional" type="button" role="tab"
                            aria-controls="v-pills-regional" aria-selected="false">
                            <i class="fas fa-globe me-2"></i> Régional & Devise
                        </button>
                        <button class="nav-link text-start" id="v-pills-stock-tab" data-bs-toggle="pill"
                            data-bs-target="#v-pills-stock" type="button" role="tab" aria-controls="v-pills-stock"
                            aria-selected="false">
                            <i class="fas fa-boxes me-2"></i> Stock & Produits
                        </button>
                        <button class="nav-link text-start" id="v-pills-interface-tab" data-bs-toggle="pill"
                            data-bs-target="#v-pills-interface" type="button" role="tab"
                            aria-controls="v-pills-interface" aria-selected="false">
                            <i class="fas fa-sliders-h me-2"></i> Interface & Système
                        </button>
                    </div>

                    <div class="mt-4 d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Enregistrer tout
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="col-md-9">
                    <div class="tab-content" id="v-pills-tabContent">

                        <!-- 1. General Settings -->
                        <div class="tab-pane fade show active" id="v-pills-general" role="tabpanel"
                            aria-labelledby="v-pills-general-tab">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white fw-bold">Informations sur l'Entreprise</div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Nom du Magasin / Entreprise</label>
                                        <input type="text" class="form-control" name="company_name"
                                            value="{{ $settings['company_name'] ?? '' }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Logo</label>
                                        <input type="file" class="form-control" name="company_logo">
                                        @if (!empty($settings['company_logo']))
                                            <div class="mt-2">
                                                <img src="{{ asset('storage/' . $settings['company_logo']) }}"
                                                    alt="Logo actuel" style="max-height: 50px;">
                                            </div>
                                        @endif
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email de contact</label>
                                            <input type="email" class="form-control" name="company_email"
                                                value="{{ $settings['company_email'] ?? '' }}">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Téléphone</label>
                                            <input type="text" class="form-control" name="company_phone"
                                                value="{{ $settings['company_phone'] ?? '' }}">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Adresse</label>
                                        <textarea class="form-control" name="company_address" rows="2">{{ $settings['company_address'] ?? '' }}</textarea>
                                    </div>

                                    <hr>
                                    <h6 class="text-muted mb-3">Identifiants Fiscaux</h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Numéro SIRET</label>
                                            <input type="text" class="form-control" name="company_siret"
                                                value="{{ $settings['company_siret'] ?? '' }}">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">TVA Intracommunautaire</label>
                                            <input type="text" class="form-control" name="company_vat"
                                                value="{{ $settings['company_vat'] ?? '' }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Regional Settings -->
                        <div class="tab-pane fade" id="v-pills-regional" role="tabpanel"
                            aria-labelledby="v-pills-regional-tab">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white fw-bold">Configuration Régionale</div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Symbole Monétaire</label>
                                            <input type="text" class="form-control" name="currency_symbol"
                                                value="{{ $settings['currency_symbol'] ?? '€' }}">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Position de la devise</label>
                                            <select class="form-select" name="currency_position">
                                                <option value="after"
                                                    {{ ($settings['currency_position'] ?? '') == 'after' ? 'selected' : '' }}>
                                                    Après le montant (100 €)</option>
                                                <option value="before"
                                                    {{ ($settings['currency_position'] ?? '') == 'before' ? 'selected' : '' }}>
                                                    Avant le montant (€ 100)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Fuseau Horaire</label>
                                        <select class="form-select" name="timezone">
                                            @foreach (timezone_identifiers_list() as $timezone)
                                                <option value="{{ $timezone }}"
                                                    {{ ($settings['timezone'] ?? 'UTC') == $timezone ? 'selected' : '' }}>
                                                    {{ $timezone }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Format de Date</label>
                                        <select class="form-select" name="date_format">
                                            <option value="d/m/Y"
                                                {{ ($settings['date_format'] ?? '') == 'd/m/Y' ? 'selected' : '' }}>
                                                JJ/MM/AAAA (31/12/2024)</option>
                                            <option value="Y-m-d"
                                                {{ ($settings['date_format'] ?? '') == 'Y-m-d' ? 'selected' : '' }}>
                                                AAAA-MM-JJ (2024-12-31)</option>
                                            <option value="m/d/Y"
                                                {{ ($settings['date_format'] ?? '') == 'm/d/Y' ? 'selected' : '' }}>
                                                MM/JJ/AAAA (12/31/2024)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Stock Settings -->
                        <div class="tab-pane fade" id="v-pills-stock" role="tabpanel"
                            aria-labelledby="v-pills-stock-tab">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white fw-bold">Paramètres de Stock</div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Seuil d'alerte global (par défaut)</label>
                                        <input type="number" class="form-control" name="global_alert_threshold"
                                            value="{{ $settings['global_alert_threshold'] ?? 5 }}">
                                        <div class="form-text">Utilisé si aucun seuil spécifique n'est défini sur le
                                            produit.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Taux de TVA par défaut (%)</label>
                                        <input type="number" step="0.01" class="form-control"
                                            name="default_tax_rate" value="{{ $settings['default_tax_rate'] ?? 20 }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Méthode de valorisation</label>
                                        <select class="form-select" name="stock_valuation_method">
                                            <option value="FIFO"
                                                {{ ($settings['stock_valuation_method'] ?? '') == 'FIFO' ? 'selected' : '' }}>
                                                FIFO (Premier entré, premier sorti)</option>
                                            <option value="CUMP"
                                                {{ ($settings['stock_valuation_method'] ?? '') == 'CUMP' ? 'selected' : '' }}>
                                                CUMP (Coût Unitaire Moyen Pondéré)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Interface & Security -->
                        <div class="tab-pane fade" id="v-pills-interface" role="tabpanel"
                            aria-labelledby="v-pills-interface-tab">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white fw-bold">Interface & Système</div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Pagination (éléments par page)</label>
                                        <select class="form-select" name="pagination_per_page">
                                            <option value="10"
                                                {{ ($settings['pagination_per_page'] ?? '') == '10' ? 'selected' : '' }}>10
                                            </option>
                                            <option value="15"
                                                {{ ($settings['pagination_per_page'] ?? '') == '15' ? 'selected' : '' }}>15
                                            </option>
                                            <option value="25"
                                                {{ ($settings['pagination_per_page'] ?? '') == '25' ? 'selected' : '' }}>25
                                            </option>
                                            <option value="50"
                                                {{ ($settings['pagination_per_page'] ?? '') == '50' ? 'selected' : '' }}>50
                                            </option>
                                        </select>
                                    </div>

                                    <hr>
                                    <h6 class="text-muted mb-3">Maintenance</h6>
                                    <div class="alert alert-info">
                                        <i class="fas fa-database me-2"></i> Sauvegarde de la base de données
                                        <p class="mb-2 mt-1 small">Lancez une sauvegarde manuelle de la base de données.
                                        </p>
                                        <form action="{{ route('admin.settings.backup') }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-dark">
                                                <i class="fas fa-play-circle me-1"></i> Lancer la sauvegarde
                                            </button>
                                        </form>
                                        <p class="mb-0 mt-2 small text-muted">La tâche sera exécutée en arrière-plan. Le
                                            fichier SQL sera stocké dans le dossier de stockage de l'application.</p>
                                    </div>

                                    @if (isset($backups) && count($backups) > 0)
                                        <h6 class="text-muted mb-3 mt-4">Historique des sauvegardes</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover border">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Fichier</th>
                                                        <th>Taille</th>
                                                        <th>Date</th>
                                                        <th class="text-end">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($backups as $backup)
                                                        <tr>
                                                            <td><i class="fas fa-file-archive text-warning me-2"></i>
                                                                {{ $backup['name'] }}</td>
                                                            <td>{{ $backup['size'] }}</td>
                                                            <td>{{ $backup['date'] }}</td>
                                                            <td class="text-end">
                                                                <a href="{{ route('admin.settings.download-backup', ['path' => $backup['path']]) }}"
                                                                    class="btn btn-xs btn-outline-primary"
                                                                    title="Télécharger">
                                                                    <i class="fas fa-download"></i>
                                                                </a>
                                                        <form action="{{ route('admin.settings.verify-backup') }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <input type="hidden" name="path" value="{{ $backup['path'] }}">
                                                            <button type="submit" class="btn btn-xs btn-outline-success" title="Vérifier l'intégrité">
                                                                <i class="fas fa-check-circle"></i>
                                                            </button>
                                                        </form>
                                                                <form action="{{ route('admin.settings.delete-backup') }}"
                                                                    method="POST" class="d-inline"
                                                                    onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette sauvegarde ?');">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <input type="hidden" name="path"
                                                                        value="{{ $backup['path'] }}">
                                                                    <button type="submit"
                                                                        class="btn btn-xs btn-outline-danger"
                                                                        title="Supprimer">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
