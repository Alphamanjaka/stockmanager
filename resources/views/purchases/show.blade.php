@extends('layouts.app-back-office')
@section('title', 'Détail de l\'Achat : ' . $purchase->reference)

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary shadow-sm">
                <i class="bi bi-arrow-left"></i> Back to Purchases
            </a>
            <div>
                <a href="{{ route('admin.purchases.edit', $purchase->id) }}" class="btn btn-primary shadow-sm">
                    <i class="bi bi-pencil"></i> Update
                </a>
                <a href="{{ route('admin.purchases.pdf', $purchase->id) }}" class="btn btn-danger shadow-sm" target="_blank">
                    <i class="bi bi-file-earmark-pdf"></i> Download as PDF
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Colonne de gauche : Détails et Items -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Purchase Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-center">Quantity</th>
                                        <th class="text-end">Unit Cost (Mga)</th>
                                        <th class="text-end">Subtotal (Mga)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($purchase->items as $item)
                                        <tr>
                                            <td>{{ $item->product->name }}</td>
                                            <td class="text-center">{{ $item->quantity }}</td>
                                            <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                                            <td class="text-end">{{ number_format($item->subtotal, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="row">
                            <div class="col-lg-5 col-sm-6 ms-auto">
                                <table class="table table-clear mb-0">
                                    <tbody>
                                        <tr>
                                            <td><strong>Total Brut</strong></td>
                                            <td class="text-end">{{ number_format($purchase->total_amount, 2) }} Mga</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Discount</strong></td>
                                            <td class="text-end text-danger">-{{ number_format($purchase->discount, 2) }}
                                                Mga
                                            </td>
                                        </tr>
                                        <tr class="table-light">
                                            <td><span class="h5">Total Net</span></td>
                                            <td class="text-end"><span
                                                    class="h5 text-success">{{ number_format($purchase->total_net, 2) }}
                                                    Mga</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colonne de droite : Statut et Fournisseur -->
            <div class="col-md-4">
                <!-- Statut -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Statut</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.purchases.updateState', $purchase->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <div class="input-group">
                                <select name="state" class="form-select">
                                    <option value="Draft" @selected($purchase->state == 'Draft')>Brouillon</option>
                                    <option value="Ordered" @selected($purchase->state == 'Ordered')>Commandé</option>
                                    <option value="Received" @selected($purchase->state == 'Received')>Reçu</option>
                                    <option value="Paid" @selected($purchase->state == 'Paid')>Payé</option>
                                </select>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-circle"></i> Mettre à jour
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Fournisseur -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Fournisseur</h5>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">{{ $purchase->supplier->name }}</h5>
                        <p class="card-text mb-1"><i class="bi bi-envelope-fill text-muted"></i>
                            {{ $purchase->supplier->email }}</p>
                        <p class="card-text"><i class="bi bi-telephone-fill text-muted"></i>
                            {{ $purchase->supplier->phone ?? 'Non renseigné' }}</p>
                        <a href="{{ route('admin.suppliers.show', $purchase->supplier->id) }}"
                            class="btn btn-sm btn-outline-primary">Voir le fournisseur</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
