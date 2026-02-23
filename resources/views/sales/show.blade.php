@extends('layouts.app-front-office')

@section('title', "Sale Details : " . $sale->reference)

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('sales.index') }}" class="btn btn-secondary shadow-sm">
            <i class="bi bi-arrow-left"></i> Back to History
        </a>
        <a href="{{ route('sales.pdf', $sale->id) }}" class="btn btn-danger shadow-sm">
            <i class="bi bi-file-earmark-pdf"></i> Download as PDF
        </a>
    </div>

    <div class="card shadow border-0">
        <div class="card-body p-5">
            <div class="row mb-4">
                <div class="col-sm-6">
                    <h5 class="mb-3 text-uppercase text-muted">Émetteur</h5>
                    <div><strong>StockMaster Pro</strong></div>
                    <div>Antananarivo, Madagascar</div>
                    <div>Email: contact@stockmaster.test</div>
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
                        @foreach($sale->items as $item)
                        <tr>
                            <td>{{ $item->product->name }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end">{{ number_format($item->unit_price, 2) }} €</td>
                            <td class="text-end">{{ number_format($item->subtotal, 2) }} €</td>
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
                                <td class="text-end">{{ number_format($sale->total_brut, 2) }} €</td>
                            </tr>
                            <tr>
                                <td><strong>Discount</strong></td>
                                <td class="text-end text-danger">-{{ number_format($sale->discount, 2) }} €</td>
                            </tr>
                            <tr class="table-light">
                                <td><span class="h5">Total Net</span></td>
                                <td class="text-end"><span class="h5 text-success">{{ number_format($sale->total_net, 2) }} €</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
