@extends('layouts.app-back-office') {{-- Adaptez à votre layout principal --}}

@section('content')
    <div class="container-fluid">
        <h1 class="h3 mb-4 text-gray-800">Modifier l'utilisateur : {{ $user->name }}</h1>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Détails du profil</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.users.update', $user) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Nom</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                name="name" value="{{ old('name', $user->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Adresse Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                                name="email" value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Rôle</label>
                        <select name="role" id="role" class="form-select @error('role') is-invalid @enderror"
                            {{ auth()->id() === $user->id ? 'disabled' : '' }}>
                            <option value="front_office" @selected(old('role', $user->role) == 'front_office')>Vendeur (Front Office)</option>
                            <option value="back_office" @selected(old('role', $user->role) == 'back_office')>Administrateur (Back Office)</option>
                        </select>
                        @if (auth()->id() === $user->id)
                            <small class="form-text text-muted">Vous ne pouvez pas modifier votre propre rôle.</small>
                        @endif
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr>

                    <h6 class="text-muted">Changer le mot de passe</h6>
                    <p class="text-muted small">Laissez les champs de mot de passe vides pour ne pas le modifier.</p>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                id="password" name="password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="password_confirmation" class="form-label">Confirmer le nouveau mot de passe</label>
                            <input type="password" class="form-control" id="password_confirmation"
                                name="password_confirmation">
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Mettre à jour
                        </button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
