@extends('layouts.app-back-office') {{-- Adaptez avec votre layout principal --}}

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Create an order (Stock shortage)</h1>
        </div>

        <p class="text-muted">
            This page suggests orders for products with low stock (less than or equal to the alert threshold).
            You can uncheck products you don't wish to order or adjust quantities before confirming.
        </p>

        @if ($groupedProducts->isEmpty())
            <div class="alert alert-success">
                <h4 class="alert-heading">🎉 Good news!</h4>
                <p>No product requires restocking at the moment.</p>
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
                                    is associated to these products in history. You will need to create an order
                                    manuellement.</p>
                            @endif
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width: 5%;">Commander</th>
                                            <th>Product</th>
                                            <th class="text-center" style="width: 10%;">Current Stock</th>
                                            <th class="text-center" style="width: 10%;">Alert Threshold</th>
                                            <th class="text-end" style="width: 15%;">Last Unit Cost</th>
                                            <th style="width: 15%;">Quantity to order</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($group['products'] as $product)
                                            <tr>
                                                <td class="text-center">
                                                    <div class="form-check d-flex justify-content-center">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="items[{{ $product->id }}][selected]" value="1"
                                                            checked>
                                                    </div>
                                                    {{-- These fields must always be present to avoid "Undefined array key" errors on submission --}}
                                                    <input type="hidden" name="items[{{ $product->id }}][supplier_id]"
                                                        value="{{ $group['supplier_id'] }}">
                                                    <input type="hidden" name="items[{{ $product->id }}][product_id]"
                                                        value="{{ $product->id }}">
                                                    <input type="hidden" name="items[{{ $product->id }}][unit_price]"
                                                        value="{{ $product->last_unit_price }}">
                                                </td>
                                                <td>{{ $product->product_name }} {{ $product->color_code?? '' }}</td>
                                                <td class="text-center">{{ $product->stock }}</td>
                                                <td class="text-center">{{ $product->alert_stock }}</td>
                                                <td class="text-end">
                                                    {{ number_format($product->last_unit_price, 2, ',', ' ') }} MGA</td>
                                                <td>
                                                    <input type="number" class="form-control form-control-sm"
                                                        name="items[{{ $product->id }}][quantity]"
                                                        value="{{ $product->suggested_quantity }}" min="1">
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
                        Create selected orders
                    </button>
                </div>
            </form>
        @endif
    </div>
@endsection
