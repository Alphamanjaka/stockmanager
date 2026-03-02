@extends('layouts.app-front-office')

@section('title', 'Gestion des Ventes')
@section('content')
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm text-center h-100 bg-success text-white">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2">Ventes Aujourd'hui</h6>
                    <p class="card-text fs-4 fw-bold">
                        {{ number_format($salesToday, 2, ',', ' ') }}
                        MGA
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm text-center h-100 bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2">Ventes ce Mois</h6>
                    <p class="card-text fs-4 fw-bold">
                        {{ number_format($salesThisMonth, 2, ',', ' ') }}
                        MGA
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm text-center h-100 bg-info text-white">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2">Nombre de Ventes</h6>
                    <p class="card-text fs-4 fw-bold">{{ $totalSales }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-gradient text-white"
                    style="background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);">
                    <h5 class="mb-0"><i class="fas fa-receipt"></i> Actions Rapides</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('sales.create') }}" class="btn btn-lg btn-success w-100 py-3">
                                <i class="fas fa-plus-circle"></i> Créer une Nouvelle Vente
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('sales.index') }}" class="btn btn-lg btn-primary w-100 py-3">
                                <i class="fas fa-list"></i> Voir Toutes les Ventes
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
