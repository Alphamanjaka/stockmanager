@extends('layouts.app-back-office')

@section('title', 'Détail Fournisseur : ' . $supplier->name)

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Retour à la liste
            </a>
            <div>
                <a href="{{ route('admin.suppliers.edit', $supplier->id) }}" class="btn btn-primary">
                    <i class="bi bi-pencil-square"></i> Modifier
                </a>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informations</h5>
                    </div>
                    <div class="card-body">
                        <h3 class="mb-3">{{ $supplier->name }}</h3>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <strong><i class="bi bi-envelope"></i> Email:</strong>
                                <span>{{ $supplier->email ?? 'Non renseigné' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong><i class="bi bi-telephone"></i> Téléphone:</strong>
                                <span>{{ $supplier->phone }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong><i class="bi bi-geo-alt"></i> Adresse:</strong>
                                <span>{{ $supplier->address ?? 'Non renseignée' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Date d'ajout:</strong>
                                <span>{{ $supplier->created_at->format('d/m/Y') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100 bg-light">
                    <div class="card-body text-center d-flex flex-column justify-content-center">
                        <h6 class="text-muted text-uppercase small">Volume d'Affaires</h6>
                        <h1 class="display-5 fw-bold text-primary">{{ number_format($totalSpent, 2) }} €</h1>
                        <p class="mb-3 text-muted">sur {{ $purchases->total() }} commandes</p>

                        @if ($lastPurchase)
                            <div class="alert alert-white border shadow-sm py-2 mb-0">
                                <small class="text-muted d-block">Dernier achat le</small>
                                <strong>{{ $lastPurchase->created_at->format('d/m/Y') }}</strong>
                            </div>
                        @else
                            <span class="badge bg-secondary">Aucun achat</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if ($topProducts->isNotEmpty())
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="bi bi-trophy"></i> Top Produits achetés</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Produit</th>
                                            <th class="text-center">Quantité Totale</th>
                                            <th class="text-end">Coût Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($topProducts as $product)
                                            <tr>
                                                <td>{{ $product->name }}</td>
                                                <td class="text-center fw-bold">{{ $product->total_qty }}</td>
                                                <td class="text-end">{{ number_format($product->total_cost, 2) }} €</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-receipt"></i> Historique des Achats</h5>
                </div>
                <div class="card-body">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Référence</th>
                                <th>Date</th>
                                <th>Montant Total</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($purchases as $purchase)
                                <tr>
                                    <td>{{ $purchase->reference }}</td>
                                    <td>{{ $purchase->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="fw-bold">{{ number_format($purchase->total_net, 2) }} €</td>
                                    <td><span class="badge bg-success">Validé</span></td>
                                    <td><button class="btn btn-sm btn-outline-secondary" disabled>Détails</button></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Aucun achat enregistré pour ce
                                        fournisseur.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-center mt-3">{{ $purchases->links() }}</div>
                </div>
            </div>
    </div>
@endsection
