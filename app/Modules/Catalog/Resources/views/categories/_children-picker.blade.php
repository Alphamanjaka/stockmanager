{{--
    Sous-catégories : deux listes (disponibles / sélectionnées) ; les cases cochées passent d'une liste à l'autre.
    À l'envoi du formulaire #category-form, les sélectionnées deviennent des champs children[].
    Variables : $available (id => nom), $selected (id => nom)
--}}
<div class="ui-field">
    <label for="child-search" class="form-label">Quick filter</label>
    <x-ui.search name="" id="child-search" placeholder="Keywords separated by commas: elixir, flower, animals" />
</div>

<div class="row g-2 align-items-center">
    <div class="col-md-5">
        <div class="form-label">Available</div>
        <div id="list-available" class="ui-picklist">
            @foreach ($available as $id => $name)
                <div class="form-check child-item" data-name="{{ strtolower($name) }}">
                    <input class="form-check-input" type="checkbox" value="{{ $id }}" id="child_{{ $id }}">
                    <label class="form-check-label" for="child_{{ $id }}">{{ $name }}</label>
                </div>
            @endforeach
        </div>
    </div>

    <div class="col-md-2 d-flex flex-md-column gap-2 justify-content-center">
        <button type="button" id="btn-move-right" class="btn btn-outline-secondary">
            Add <i class="bi bi-chevron-right" aria-hidden="true"></i>
        </button>
        <button type="button" id="btn-move-left" class="btn btn-outline-secondary">
            <i class="bi bi-chevron-left" aria-hidden="true"></i> Remove
        </button>
    </div>

    <div class="col-md-5">
        <div class="form-label">Selected sub-categories</div>
        <div id="list-selected" class="ui-picklist">
            @foreach ($selected as $id => $name)
                <div class="form-check child-item" data-name="{{ strtolower($name) }}">
                    <input class="form-check-input" type="checkbox" value="{{ $id }}" id="child_{{ $id }}">
                    <label class="form-check-label" for="child_{{ $id }}">{{ $name }}</label>
                </div>
            @endforeach
        </div>
    </div>
</div>
<div id="hidden-inputs-container"></div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const listAvailable = document.getElementById('list-available');
            const listSelected = document.getElementById('list-selected');

            // Filtre : un des mots-clés suffit
            document.getElementById('child-search').addEventListener('input', function () {
                const terms = this.value.toLowerCase().split(',').map(s => s.trim()).filter(Boolean);
                document.querySelectorAll('.child-item').forEach(item => {
                    item.hidden = terms.length > 0 && !terms.some(term => item.dataset.name.includes(term));
                });
            });

            // Les cases cochées passent dans l'autre liste
            const move = (from, to) => from.querySelectorAll('input:checked').forEach(checkbox => {
                checkbox.checked = false;
                to.appendChild(checkbox.closest('.child-item'));
            });
            document.getElementById('btn-move-right').addEventListener('click', () => move(listAvailable, listSelected));
            document.getElementById('btn-move-left').addEventListener('click', () => move(listSelected, listAvailable));

            // À l'envoi, les sous-catégories sélectionnées deviennent des champs children[]
            document.getElementById('category-form').addEventListener('submit', function () {
                const container = document.getElementById('hidden-inputs-container');
                container.replaceChildren();
                listSelected.querySelectorAll('input[type="checkbox"]').forEach(input => {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'children[]';
                    hidden.value = input.value;
                    container.appendChild(hidden);
                });
            });
        });
    </script>
@endpush
