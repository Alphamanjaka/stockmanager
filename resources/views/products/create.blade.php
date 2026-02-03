@extends('layouts.app-back-office')

@section('title', 'Ajouter un nouveau produit')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Détails du produit</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.products.store') }}" method="POST">
                    @csrf <div class="mb-3">
                        <label for="name" class="form-label">Nom du produit</label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label">Catégorie</label>
                            <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror">
                                <option value="">Choisir une catégorie</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Prix (€)</label>
                            <input type="number" step="0.01" name="price" id="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price') }}">
                            @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="quantity_stock" class="form-label">Stock Initial</label>
                        <input type="number" name="quantity_stock" id="quantity_stock" class="form-control @error('quantity_stock') is-invalid @enderror" value="{{ old('quantity_stock', 0) }}">
                        @error('quantity_stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description (Optionnel)</label>
                        <textarea name="description" id="description" rows="3" class="form-control">{{ old('description') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Annuler</a>
                        <button type="submit" class="btn btn-success px-4">Enregistrer le produit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
