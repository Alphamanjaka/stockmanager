@extends('layouts.app-back-office')

@section('title', '- Settings')

@section('content')
    <div class="container">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row">
            <!-- Sidebar Navigation (Vertical Tabs) -->
            <div class="col-md-3 mb-4">
                <div class="nav flex-column nav-pills me-3" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                    <button class="nav-link active text-start" id="v-pills-general-tab" data-bs-toggle="pill"
                        data-bs-target="#v-pills-general" type="button" role="tab" aria-controls="v-pills-general"
                        aria-selected="true">
                        <i class="fas fa-building me-2"></i> Company
                    </button>
                    <button class="nav-link text-start" id="v-pills-regional-tab" data-bs-toggle="pill"
                        data-bs-target="#v-pills-regional" type="button" role="tab" aria-controls="v-pills-regional"
                        aria-selected="false">
                        <i class="fas fa-globe me-2"></i> Regional & Currency
                    </button>
                    <button class="nav-link text-start" id="v-pills-stock-tab" data-bs-toggle="pill"
                        data-bs-target="#v-pills-stock" type="button" role="tab" aria-controls="v-pills-stock"
                        aria-selected="false">
                        <i class="fas fa-boxes me-2"></i> Stock & Products
                    </button>
                    <button class="nav-link text-start" id="v-pills-interface-tab" data-bs-toggle="pill"
                        data-bs-target="#v-pills-interface" type="button" role="tab" aria-controls="v-pills-interface"
                        aria-selected="false">
                        <i class="fas fa-sliders-h me-2"></i> Interface & System
                    </button>
                    <button class="nav-link text-start" id="v-pills-backup-tab" data-bs-toggle="pill"
                        data-bs-target="#v-pills-backup" type="button" role="tab" aria-controls="v-pills-backup"
                        aria-selected="false">
                        <i class="fas fa-database me-2"></i> Backups
                    </button>
                </div>

                <div class="mt-4 d-grid">
                    <button type="submit" class="btn btn-primary" form="settings-form">
                        <i class="fas fa-save me-2"></i> Save all
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="col-md-9">
                <form id="settings-form" action="{{ route('admin.settings.update', ['setting' => 1]) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="tab-content" id="v-pills-tabContent">
                        <div class="tab-pane fade show active" id="v-pills-general" role="tabpanel"
                            aria-labelledby="v-pills-general-tab">
                            @include('settings.partials.general')
                        </div>

                        <div class="tab-pane fade" id="v-pills-regional" role="tabpanel"
                            aria-labelledby="v-pills-regional-tab">
                            @include('settings.partials.regional')
                        </div>

                        <div class="tab-pane fade" id="v-pills-stock" role="tabpanel" aria-labelledby="v-pills-stock-tab">
                            @include('settings.partials.stock_products')
                        </div>

                        <div class="tab-pane fade" id="v-pills-interface" role="tabpanel"
                            aria-labelledby="v-pills-interface-tab">
                            @include('settings.partials.interface')
                        </div>

                        <div class="tab-pane fade" id="v-pills-backup" role="tabpanel" aria-labelledby="v-pills-backup-tab">
                            @include('settings.partials.backup')
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endsection

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Utilise la délégation d'événements pour gérer les clics sur les boutons d'action
                document.body.addEventListener('click', function(e) {
                    const button = e.target.closest('.backup-action-btn');
                    if (!button) return;

                    e.preventDefault();

                    const action = button.dataset.action;
                    const method = button.dataset.method || 'POST';
                    const path = button.dataset.path;
                    const confirmMessage = button.dataset.confirm;

                    const proceed = () => {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = action;
                        form.style.display = 'none';

                        // Récupère le jeton CSRF depuis la balise meta (pratique standard dans Laravel)
                        const csrfTokenEl = document.querySelector('meta[name="csrf-token"]');
                        if (!csrfTokenEl) {
                            console.error('CSRF token meta tag not found!');
                            alert('Erreur de sécurité : Jeton CSRF manquant.');
                            return;
                        }

                        const csrfInput = document.createElement('input');
                        csrfInput.name = '_token';
                        csrfInput.value = csrfTokenEl.content;
                        form.appendChild(csrfInput);

                        // Ajoute le champ _method pour les requêtes autres que POST (DELETE, PUT, PATCH)
                        if (method.toUpperCase() !== 'POST') {
                            const methodInput = document.createElement('input');
                            methodInput.name = '_method';
                            methodInput.value = method;
                            form.appendChild(methodInput);
                        }

                        // Ajoute le chemin du fichier si nécessaire
                        if (path) {
                            const pathInput = document.createElement('input');
                            pathInput.name = 'path';
                            pathInput.value = path;
                            form.appendChild(pathInput);
                        }

                        document.body.appendChild(form);
                        form.submit();
                    };

                    // Affiche une confirmation si demandée
                    if (confirmMessage) {
                        if (confirm(confirmMessage)) {
                            proceed();
                        }
                    } else {
                        proceed();
                    }
                });
            });
        </script>
    @endpush
