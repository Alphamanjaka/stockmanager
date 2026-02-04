@extends('layouts.app-back-office')
@section('title', 'Détails du mouvement de stock')

@section('content')
    <h1>Détails du Mouvement de Stock #{{ $stockMovement->id }}</h1>
    <div class="card shadow-sm">
        <div class="card-body">
            <p><strong>Produit :</strong> {{ $stockMovement->product->name }}</p>
            <p><strong>Type de mouvement :</strong> {{ $stockMovement->type }}</p>
            <p><strong>Quantité :</strong> {{ $stockMovement->quantity }}</p>
            <p><strong>Stock avant :</strong> {{ $stockMovement->stock_before }}</p>
            <p><strong>Stock après :</strong> {{ $stockMovement->stock_after }}</p>
            <p><strong>Raison :</strong> {{ $stockMovement->reason ?? 'N/A' }}</p>
            <p><strong>Date :</strong> {{ $stockMovement->created_at }}</p>
        </div>
        <div class="card-footer">
            <a href="{{ route('admin.movements.index') }}" class="btn btn-secondary">Retour à la liste</a>
        </div>
    </div>
@endsection
