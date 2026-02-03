@extends('layouts.app')

@section('title', 'Historique des Ventes')

@section('content')

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card bg-success text-white shadow-sm">
            <div class="card-body">
                <h6>Chiffre d'Affaires Total (Net)</h6>
                <h3>{{ number_format($total_revenue, 2) }} €</h3>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-danger text-white shadow-sm">
            <div class="card-body">
                <h6>Total des Remises Accordées</h6>
                <h3>{{ number_format($total_discount, 2) }} €</h3>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Transactions Récentes</h5>
        <a href="{{ route('sales.create') }}" class="btn btn-primary btn-sm">+ Nouvelle Vente</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Référence</th>
                    <th>Date</th>
                    <th>Total Brut</th>
                    <th>Remise</th>
                    <th>Total Net</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sales as $sale)
                <tr>
                    <td class="fw-bold">{{ $sale->reference }}</td>
                    <td>{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ number_format($sale->total_brut, 2) }} €</td>
                    <td class="text-danger">-{{ number_format($sale->discount, 2) }} €</td>
                    <td class="fw-bold text-success">{{ number_format($sale->total_net, 2) }} €</td>
                    <td class="text-center">
                        <a href="{{ route('sales.show', $sale->id) }}" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-eye"></i> Détails
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $sales->links() }}
    </div>
</div>
@endsection
