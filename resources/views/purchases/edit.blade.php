@extends('layouts.app-back-office')
@section('title', 'Modifier l\'Achat')

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Modifier l'achat</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.purchases.update', $purchase->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="supplier_id" class="form-label">Fournisseur</label>
                            <select name="supplier_id" id="supplier_id" class="form-control">
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}"
                                        {{ old('supplier_id', $purchase->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="purchase_date" class="form-label">Date d'achat</label>
                            <input type="datetime-local" name="purchase_date" id="purchase_date" class="form-control" readonly
                                value="{{ old('purchase_date', $purchase->created_at->format('Y-m-d H:i')) }}">
                        </div>
                        <div class="mb-3">
                            <label for="total_amount" class="form-label">Montant total</label>
                            <input type="number" step="0.01" name="total_amount" id="total_amount" class="form-control" readonly
                                value="{{ old('total_amount', $purchase->total_amount) }}">
                        </div>
                        <div class="d-flex justify-content-between">
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
