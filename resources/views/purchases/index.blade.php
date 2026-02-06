@extends('layouts.app-back-office')

@section('title', 'Achats')

@section('content')
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-info text-white shadow-sm">
                <div class="card-body">
                    <h6>Montant Total des Achats (Net)</h6>
                    <h3>{{ number_format($totalSpent, 2) }} €</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white shadow-sm">
                <div class="card-body">
                    <h6>Total des Remises sur Achats</h6>
                    <h3>{{ number_format($totalDiscounts, 2) }} €</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white shadow-sm">
                <div class="card-body">
                    <h6>Nombre Total d'Achats</h6>
                    <h3>{{ $totalPurchases }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body">
                    <h6>Valeur Moyenne des Achats</h6>
                    <h3>{{ number_format($averagePurchaseValue, 2) }} €</h3>
                </div>
            </div>
        </div>
    </div>
    <!-- Filtre de recherche -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form action="{{ url('/admin/purchases') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Référence, Fournisseur..."
                        value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="state" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="Draft" @selected(request('state') == 'Draft')>Brouillon</option>
                        <option value="Ordered" @selected(request('state') == 'Ordered')>Commandé</option>
                        <option value="Received" @selected(request('state') == 'Received')>Reçu</option>
                        <option value="Paid" @selected(request('state') == 'Paid')>Payé</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Rechercher</button>
                    <a href="{{ url('/admin/purchases') }}" class="btn btn-outline-secondary"
                        rel="noopener">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>
    <!-- Fin du filtre de recherche -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Transactions Récentes</h5>
            <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary btn-sm">+ Nouvel Achat</a>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Référence</th>
                        <th>Montant Total</th>
                        <th>Remise</th>
                        <th>Statut</th>
                        <th>Total Net</th>
                        <th>Date d'Achat</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($purchases as $purchase)
                        <tr>
                            <td>{{ $purchase->id }}</td>
                            <td>{{ $purchase->reference }}</td>
                            <td>{{ number_format($purchase->total_amount, 2) }} €</td>
                            <td class="text-danger">{{ number_format($purchase->discount, 2) }} €</td>
                            <td>
                                @php
                                    $badgeClass = match ($purchase->state) {
                                        'Draft' => 'bg-secondary',
                                        'Ordered' => 'bg-info',
                                        'Received' => 'bg-success',
                                        'Paid' => 'bg-primary',
                                        default => 'bg-light text-dark',
                                    };
                                    $stateText = match ($purchase->state) {
                                        'Draft' => 'Brouillon',
                                        'Ordered' => 'Commandé',
                                        'Received' => 'Reçu',
                                        'Paid' => 'Payé',
                                        default => $purchase->state,
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $stateText }}</span>
                            </td>
                            <td class="fw-bold text-success">{{ number_format($purchase->total_net, 2) }} €</td>
                            <td>{{ $purchase->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.purchases.show', $purchase->id) }}" class="btn btn-sm btn-info"
                                    title="Voir"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('admin.purchases.edit', $purchase->id) }}" class="btn btn-sm btn-primary"
                                    title="Modifier"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('admin.purchases.destroy', $purchase->id) }}" method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ? Cette action est irréversible.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Supprimer"><i
                                            class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach

                </tbody>
            </table>
        </div>
    </div>
@endsection
