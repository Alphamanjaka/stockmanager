@extends('layouts.app-back-office')

@section('title', 'Ajouter une nouvelle catégorie')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Détails de la catégorie</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.categories.store') }}" method="POST" id="category-form">
                        @csrf
                        <div class="mb-3">
                            <select name="parents_id" id="parents_id" class="form-control" value="{{ old('parents_id') }}">
                                <option value="">Sélectionnez une catégorie parente</option>
                                @foreach ($categories as $id => $name)
                                    <option value="{{ $id }}" {{ old('parents_id') == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="color_id" class="form-label">Couleur</label>
                            <select name="color_id" id="color_id" class="form-select @error('color_id') is-invalid @enderror">
                                <option value="">Sélectionner une couleur</option>
                                @foreach($colors as )
                                    <option value="{{ ->id }}" {{ old('color_id', ->color_id ?? '') == ->id ? 'selected' : '' }}>
                                        {{ ->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('color_id')
                                <div class="invalid-feedback">{{  }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom de la catégorie</label>
                            <input type="text" name="name" id="name"
                                class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <input type="text" id="description" name="description" class="form-control"
                                value="{{ old('description') }}">
                        </div>

                        {{-- Gestion des enfants (Double Liste) --}}
                        <hr>
                        <h5 class="mb-3">Sous-catégories (Enfants)</h5>

                        <div class="mb-3">
                            <label for="child-search" class="form-label">Recherche rapide (mots clés séparés par des
                                virgules : <i>elixir, fleur, animaux</i>)</label>
                            <input type="text" id="child-search" class="form-control"
                                placeholder="Filtrer les listes...">
                        </div>

                        <div class="row">
                            <div class="col-md-5">
                                <label class="fw-bold">Disponibles</label>
                                <div id="list-available" class="border rounded p-2 bg-light"
                                    style="height: 250px; overflow-y: auto;">
                                    @foreach ($categoriesChildrenAvalaibles as $id => $name)
                                         {{-- On n'affiche pas la catégorie elle-même ni sa catégorie parente --}}
                                        <div class="form-check child-item" data-name="{{ strtolower($name) }}">
                                            <input class="form-check-input check-available" type="checkbox"
                                                value="{{ $id }}" id="avail_{{ $id }}">
                                            <label class="form-check-label" for="avail_{{ $id }}">
                                                {{ $name }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="col-md-2 d-flex flex-column justify-content-center align-items-center">
                                <button type="button" id="btn-move-right" class="btn btn-primary mb-2" title="Ajouter">
                                    <i class="bi bi-arrow-right"></i> Ajouter &gt;
                                </button>
                                <button type="button" id="btn-move-left" class="btn btn-secondary" title="Retirer">
                                    &lt; Retirer <i class="bi bi-arrow-left"></i>
                                </button>
                            </div>

                            <div class="col-md-5">
                                <label class="fw-bold">Enfants sélectionnés</label>
                                <div id="list-selected" class="border rounded p-2 bg-white"
                                    style="height: 250px; overflow-y: auto;">
                                    {{-- Vide à la création --}}
                                </div>
                            </div>
                        </div>
                        {{-- Conteneur pour les inputs cachés qui seront envoyés --}}
                        <div id="hidden-inputs-container"></div>
                        <hr>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">Annuler</a>
                            <button type="submit" class="btn btn-success px-4">Enregistrer la catégorie</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const listAvailable = document.getElementById('list-available');
            const listSelected = document.getElementById('list-selected');
            const btnRight = document.getElementById('btn-move-right');
            const btnLeft = document.getElementById('btn-move-left');
            const searchInput = document.getElementById('child-search');
            const hiddenContainer = document.getElementById('hidden-inputs-container');
            const form = document.getElementById('category-form');

            // Fonction de recherche élastique
            searchInput.addEventListener('keyup', function() {
                const terms = this.value.toLowerCase().split(',').map(s => s.trim()).filter(s => s);
                const allItems = document.querySelectorAll('.child-item');

                allItems.forEach(item => {
                    const name = item.getAttribute('data-name');
                    const match = terms.length === 0 || terms.some(term => name.includes(term));
                    item.style.display = match ? 'block' : 'none';
                });
            });

            // Déplacer vers la droite (Ajouter)
            btnRight.addEventListener('click', function() {
                const checked = listAvailable.querySelectorAll('.check-available:checked');
                checked.forEach(checkbox => {
                    const itemDiv = checkbox.closest('.child-item');
                    checkbox.checked = false;
                    checkbox.classList.remove('check-available');
                    checkbox.classList.add('check-selected');
                    listSelected.appendChild(itemDiv);
                });
            });

            // Déplacer vers la gauche (Retirer)
            btnLeft.addEventListener('click', function() {
                const checked = listSelected.querySelectorAll('.check-selected:checked');
                checked.forEach(checkbox => {
                    const itemDiv = checkbox.closest('.child-item');
                    checkbox.checked = false;
                    checkbox.classList.remove('check-selected');
                    checkbox.classList.add('check-available');
                    listAvailable.appendChild(itemDiv);
                });
            });

            // Avant la soumission, créer les inputs cachés
            form.addEventListener('submit', function() {
                hiddenContainer.innerHTML = '';
                const selectedItems = listSelected.querySelectorAll('input[type="checkbox"]');
                selectedItems.forEach(input => {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'children[]';
                    hiddenInput.value = input.value;
                    hiddenContainer.appendChild(hiddenInput);
                });
            });
        });
    </script>
@endpush
