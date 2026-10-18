{{--
    Modèle d'options de la catégorie : proposé (et modifiable) à la création d'un produit de cette catégorie.
    Variable attendue : $template (tableau [["name" => ..., "values" => [...]]] ou null)
--}}
@php
    $rows = old('option_template', $template ?? []) ?: [];
@endphp

<x-ui.card title="Product options for this category"
    subtitle="Suggested when creating a product in this category, and editable there. The brand is chosen separately for each variant.">
    <p class="small text-muted mb-3">
        Example for wigs: <em>Color</em> (Black, Brown), <em>Length</em> (30 cm, 40 cm).
    </p>

    <div id="option-template-rows">
        @foreach ($rows as $i => $row)
            <div class="row g-2 mb-2 option-template-row">
                <div class="col-md-4">
                    <input type="text" name="option_template[{{ $i }}][name]" class="form-control" placeholder="Option (e.g. Size)"
                        value="{{ $row['name'] ?? '' }}" maxlength="50" aria-label="Option name">
                </div>
                <div class="col">
                    <input type="text" name="option_template[{{ $i }}][values]" class="form-control"
                        placeholder="Suggested values, comma separated (optional)" aria-label="Suggested values"
                        value="{{ is_array($row['values'] ?? null) ? implode(', ', $row['values']) : ($row['values'] ?? '') }}">
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-icon btn-icon-danger remove-option-template-row" title="Remove" aria-label="Remove">
                        <i class="bi bi-trash3" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        @endforeach
    </div>
    @error('option_template')
        <div class="text-danger small mb-2">{{ $message }}</div>
    @enderror

    <button type="button" class="btn btn-sm btn-outline-secondary" id="add-option-template-row">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> Add an option
    </button>
</x-ui.card>

@push('scripts')
    <script>
        (function () {
            const container = document.getElementById('option-template-rows');
            let index = {{ count($rows) }};

            document.getElementById('add-option-template-row').addEventListener('click', function () {
                const row = document.createElement('div');
                row.className = 'row g-2 mb-2 option-template-row';
                row.innerHTML = `
                    <div class="col-md-4"><input type="text" name="option_template[${index}][name]" class="form-control" placeholder="Option (e.g. Size)" maxlength="50" aria-label="Option name"></div>
                    <div class="col"><input type="text" name="option_template[${index}][values]" class="form-control" placeholder="Suggested values, comma separated (optional)" aria-label="Suggested values"></div>
                    <div class="col-auto"><button type="button" class="btn btn-icon btn-icon-danger remove-option-template-row" title="Remove" aria-label="Remove"><i class="bi bi-trash3" aria-hidden="true"></i></button></div>`;
                container.appendChild(row);
                index++;
                row.querySelector('input').focus();
            });

            container.addEventListener('click', function (event) {
                const button = event.target.closest('.remove-option-template-row');
                if (button) {
                    button.closest('.option-template-row').remove();
                }
            });
        })();
    </script>
@endpush
