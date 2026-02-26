@extends('layouts.app-back-office') {{-- Adaptez avec votre layout principal --}}

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Créer une commande (Rupture de stock)</h1>
        </div>

        <p class="text-muted">
            Cette page suggère des commandes pour les produits dont le stock est bas (inférieur ou égal au seuil d'alerte).
            Vous pouvez décocher les produits que vous ne souhaitez pas commander ou ajuster les quantités avant de valider.
        </p>

        @if ($groupedProducts->isEmpty())
            <div class="alert alert-success">
                <h4 class="alert-heading">🎉 Bonne nouvelle !</h4>
                <p>Aucun produit ne nécessite de réapprovisionnement pour le moment.</p>
            </div>
        @else
            <form action="{{ route('admin.purchases.storeFromShortage') }}" method="POST">
                @csrf

                @foreach ($groupedProducts as $group)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="h5 mb-0">
                                Fournisseur : <strong>{{ $group['supplier_name'] }}</strong>
                                @if (!$group['supplier_id'])
                                    <span class="badge bg-warning text-dark ms-2">Action requise</span>
                                @endif
                            </h3>
                            @if (!$group['supplier_id'])
                                <p class="mb-0 small text-muted">Impossible de créer une commande car aucun fournisseur
                                    n'est associé à ces produits dans l'historique. Vous devrez créer une commande
                                    manuellement.</p>
                            @endif
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width: 5%;">Commander</th>
                                            <th>Produit</th>
                                            <th class="text-center" style="width: 10%;">Stock Actuel</th>
                                            <th class="text-center" style="width: 10%;">Seuil d'Alerte</th>
                                            <th class="text-end" style="width: 15%;">Dernier Coût Unitaire</th>
                                            <th style="width: 15%;">Quantité à commander</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($group['products'] as $product)
                                            <tr>
                                                <td class="text-center">
                                                    <div class="form-check d-flex justify-content-center">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="items[{{ $product->id }}][selected]" value="1"
                                                            @if ($group['supplier_id']) checked @else disabled @endif>
                                                    </div>
                                                    @if ($group['supplier_id'])
                                                        <input type="hidden" name="items[{{ $product->id }}][supplier_id]"
                                                            value="{{ $group['supplier_id'] }}">
                                                        <input type="hidden" name="items[{{ $product->id }}][product_id]"
                                                            value="{{ $product->id }}">
                                                        <input type="hidden" name="items[{{ $product->id }}][unit_price]"
                                                            value="{{ $product->last_unit_price }}">
                                                    @endif
                                                </td>
                                                <td>{{ $product->name }}</td>
                                                <td class="text-center">{{ $product->quantity_stock }}</td>
                                                <td class="text-center">{{ $product->alert_stock }}</td>
                                                <td class="text-end">
                                                    {{ number_format($product->last_unit_price, 2, ',', ' ') }} €</td>
                                                <td>
                                                    <input type="number" class="form-control form-control-sm"
                                                        name="items[{{ $product->id }}][quantity]"
                                                        value="{{ $product->suggested_quantity }}" min="1"
                                                        @if (!$group['supplier_id']) disabled @endif>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        Créer les commandes sélectionnées
                    </button>
                </div>
            </form>
        @endif
    </div>
@endsection
