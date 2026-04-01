@extends('layouts.app-back-office')

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Ajouter une nouvelle couleur</h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.colors.store') }}" method="POST">
                            @csrf

                            <div class="form-group">
                                <label for="name">Nom de la couleur <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name"
                                    class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}"
                                    placeholder="Ex: Rouge vif" required>
                                @error('name')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="code">Code Couleur (Hexadécimal ou CSS)</label>
                                <div class="input-group">
                                    <input type="text" name="code" id="code"
                                        class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}"
                                        placeholder="Ex: #FF0000">
                                    <div class="input-group-append">
                                        <input type="color" class="form-control" style="width: 45px; padding: 2px;"
                                            oninput="document.getElementById('code').value = this.value">
                                    </div>
                                </div>
                                @error('code')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Vous pouvez saisir un code hexadécimal ou utiliser le
                                    sélecteur.</small>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">Enregistrer</button>
                                <a href="{{ route('admin.colors.index') }}" class="btn btn-secondary">Annuler</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
