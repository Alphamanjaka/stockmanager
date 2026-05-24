@extends('layouts.app-back-office')

@section('title', 'Sale Details : ' . $sale->reference . ' by ' . ($sale->user->name ?? 'N/A'))

@section('content')
    <div class="container">
        {{-- ajouter une section sur l'information du vendeur qui permettra de le contacter rapidement  --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title">Vendeur : {{ $sale->user->name ?? 'N/A' }}</h5>
                <p class="card-text">Email : {{ $sale->user->email ?? 'N/A' }}</p>
                <p class="card-text">Téléphone : {{ $sale->user->phone ?? 'N/A' }}</p>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="{{ route('admin.sales.index') }}" class="btn btn-secondary shadow-sm">
                <i class="bi bi-arrow-left"></i> Back to History
            </a>
            <a href="{{ route('admin.sales.pdf', $sale->id) }}" class="btn btn-danger shadow-sm">
                <i class="bi bi-file-earmark-pdf"></i> Download as PDF
            </a>
        </div>

        <div class="card shadow border-0">
            <div class="card-body p-5">
                <div class="row mb-4">
                    <div class="col-sm-6">
                        <h5 class="mb-3 text-uppercase text-muted">Émetteur</h5>
                        <div><strong>{{ $sale->company_name }}</strong></div>
                        <div>{{ $sale->company_address ?? ''}}</div>
                        <div>Tél: {{ $sale->company_phone ?? '+261 34 22 12345' }}</div>
                        <div>Email: {{ $sale->company_email ?? 'contact@stockmaster.test' }}</div>
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <h5 class="mb-3 text-uppercase text-muted">Détails Facture</h5>
                        <div class="h4 text-primary">{{ $sale->reference }}</div>
                        <div>Date : {{ $sale->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                </div>

                <div class="table-responsive-sm">
                    <table class="table table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sale->items as $item)
                                <tr>
                                    <td>{{ $item->productColor->product->name }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end">
                                        {{ number_format($item->unit_price, 2) }} {{ $sale->currency_symbol }}
                                    </td>
                                    <td class="text-end">{{ number_format($item->subtotal, 2) }}
                                        {{ $sale->currency_symbol }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="row">
                    <div class="col-lg-4 col-sm-5 ms-auto">
                        <table class="table table-clear">
                            <tbody>
                                <tr>
                                    <td><strong>Total Gross</strong></td>
                                    <td class="text-end">{{ number_format($sale->total_brut, 2) }}
                                        {{ $sale->currency_symbol }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Discount</strong></td>
                                    <td class="text-end text-danger">-{{ number_format($sale->discount, 2) }}
                                        {{ $sale->currency_symbol }}</td>
                                </tr>
                                <tr class="table-light">
                                    <td><span class="h5">Total Net</span></td>
                                    <td class="text-end"><span
                                            class="h5 text-success">{{ number_format($sale->total_net, 2) }}
                                            {{ $sale->currency_symbol }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
