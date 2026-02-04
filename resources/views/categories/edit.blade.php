@extends('layouts.app-back-office')
@section('title', 'Modifier la Catégorie')

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Modifier la catégorie</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.categories.update', $category->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="parent_id" class="form-label">Catégorie Parente</label>
                            <select name="parent_id" id="parent_id" class="form-control">
                                <option value="">Sélectionnez une catégorie parente</option>
                                @foreach ($categories as $cat)
                                    @if ($cat->id !== $category->id)
                                        {{-- Empêcher de se sélectionner soi-même --}}
                                        <option value="{{ $cat->id }}"
                                            {{ old('parent_id', $category->parent_id) == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            <div class="form-text">Laisser vide si c'est une catégorie principale.</div>
                        </div>
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom de la catégorie</label>
                            <input type="text" name="name" id="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $category->name) }}">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <input type="text" id="description" name="description" class="form-control"
                                value="{{ old('description', $category->description) }}">
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">Annuler</a>
                            <button type="submit" class="btn btn-success px-4">Mettre à jour la catégorie</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
