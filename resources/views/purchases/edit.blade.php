@extends('layouts.app-back-office')
@section('title', 'Modifier l\'Achat ' . $purchase->reference)

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Modification de l'achat #{{ $purchase->reference }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.purchases.update', $purchase->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="supplier_id" class="form-label">Fournisseur</label>
                                <select name="supplier_id" id="supplier_id" class="form-select">
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}"
                                            {{ old('supplier_id', $purchase->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="purchase_date" class="form-label">Date d'achat</label>
                                <input type="text" id="purchase_date" class="form-control" readonly
                                    value="{{ $purchase->created_at->format('d/m/Y H:i') }}">
                            </div>
                        </div>

                        <hr>
                        <h5 class="mt-4 mb-3">Articles de l'achat (non modifiables)</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produit</th>
                                        <th class="text-center">Quantité</th>
                                        <th class="text-end">Coût Unitaire</th>
                                        <th class="text-end">Sous-total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($purchase->items as $item)
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

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('admin.purchases.index') }}" class="btn btn-secondary">
                                Annuler</a>
                            <button type="submit" class="btn btn-success px-4">Mettre à jour l'achat</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
