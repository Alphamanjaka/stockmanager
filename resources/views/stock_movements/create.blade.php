@extends('layouts.app-back-office')
@section('title', 'Créer un mouvement de stock')
@section('content')
    {{-- implementer le formulaire de création de mouvement de stock ici --}}
    <div class="container mt-4">
        <h1>Créer un mouvement de stock</h1>
        <form action="{{ route('admin.movements.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label">Produit</label>
                <select name="product_color_id" class="form-select" required>
                    @foreach ($productColors as $color)
                        <option value="{{ $color->id }}">{{ $color->product->name }} - {{ $color->color->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label    ">Quantité</label>
                <input type="number" name="quantity" class="form-control" min="1" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Type de mouvement</label>
                <select name="type" class="form-select" required>
                    <option value="in">Entrée</option>
                    <option value="out">Sortie</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Raison (optionnel)</label>
                <input type="text" name="reason" class="form-control" maxlength="255">
            </div>
            <button type="submit" class="btn btn-primary">Créer</button>
        </form>
    </div>
@endsection

@push('scripts')
@endpush
