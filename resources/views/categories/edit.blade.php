@extends('layouts.app-back-office')
@section('title', 'Modifier la Catégorie')

@section('content')
    <form id="category-form" action="{{ route('admin.categories.update', $category->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row">
            {{-- Colonne de gauche pour les informations principales --}}
            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Informations de la catégorie</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="parent_id" class="form-label">Catégorie Parente</label>
                            <select name="parent_id" id="parent_id" class="form-control">
                                <option value="">Sélectionnez une catégorie parente</option>
                                @foreach ($categoriesParent as $id => $name)
                                    @if ($id !== $category->id)
                                        {{-- Empêcher de se sélectionner soi-même --}}
                                        <option value="{{ $id }}"
                                            {{ old('parent_id', $category->parent_id) == $id ? 'selected' : '' }}>
                                            {{ $name }}
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
                    </div>
                </div>
            </div>

            {{-- Colonne de droite pour la gestion des enfants --}}
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Gestion des sous-catégories (Enfants)</h5>
                    </div>
                    <div class="card-body">
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
                                    @php
                                        $currentChildrenIds = $category->children->pluck('id')->toArray();
                                    @endphp
                                    @foreach ($categories as $id => $name) {{-- On n'affiche pas la catégorie elle-même ni sa catégorie parente --}}
                                        @if ($id !== $category->id && !in_array($id, $currentChildrenIds))
                                            <div class="form-check child-item" data-name="{{ strtolower($name) }}">
                                                <input class="form-check-input check-available" type="checkbox"
                                                    value="{{ $id }}" id="avail_{{ $id }}">
                                                <label class="form-check-label" for="avail_{{ $id }}">
                                                    {{ $name }}
                                                </label>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>

                            <div class="col-md-2 d-flex flex-column justify-content-center align-items-center">
                                <button type="button" id="btn-move-right" class="btn btn-primary mb-2 w-100"
                                    title="Ajouter">
                                    <i class="bi bi-arrow-right"></i> Ajouter &gt;
                                </button>
                                <button type="button" id="btn-move-left" class="btn btn-secondary w-100" title="Retirer">
                                    &lt; Retirer <i class="bi bi-arrow-left"></i>
                                </button>
                            </div>

                            <div class="col-md-5">
                                <label class="fw-bold">Enfants sélectionnés</label>
                                <div id="list-selected" class="border rounded p-2 bg-light"
                                    style="height: 250px; overflow-y: auto;">
                                    @foreach ($category->children as $child)
                                        <div class="form-check child-item" data-name="{{ strtolower($child->name) }}">
                                            <input class="form-check-input check-selected" type="checkbox"
                                                value="{{ $child->id }}" id="sel_{{ $child->id }}">
                                            <label class="form-check-label" for="sel_{{ $child->id }}">
                                                {{ $child->name }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div id="hidden-inputs-container"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mt-4">
            <div class="card-body d-flex justify-content-between">
                <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-success px-4">Mettre à jour la catégorie</button>
            </div>
        </div>
    </form>
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
