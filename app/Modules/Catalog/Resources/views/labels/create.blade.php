{{--
    Préparation des étiquettes code-barres (LabelController) : variantes et nombre d'étiquettes, format de la
    planche, puis PDF ouvert dans un nouvel onglet pour l'impression.
    $variants : variantes en vente ; $selected : sélection de départ [['variant' => ..., 'quantity' => n]].
--}}
@extends('layouts.app-back-office')

@section('title', __('Barcode labels'))
@section('subtitle', __('Print the barcode, name and price of your products, on an A4 sheet or a label roll.'))

@php
    $formats = [
        'a4-3x8' => __('A4 sheet, 24 labels (70 × 37 mm)'),
        'a4-3x7' => __('A4 sheet, 21 labels (63.5 × 38.1 mm)'),
        'a4-4x10' => __('A4 sheet, 40 labels (52.5 × 29.7 mm)'),
        'roll-50x30' => __('Roll, one label per page (50 × 30 mm)'),
    ];
    $perPage = collect(\App\Modules\Catalog\Support\LabelFormat::keys())
        ->mapWithKeys(fn ($key) => [$key => \App\Modules\Catalog\Support\LabelFormat::get($key)['per_page']]);
@endphp

@section('content')
    <x-ui.errors />

    <div class="row g-3">
        {{-- Choix des variantes --}}
        <div class="col-lg-5">
            <x-ui.card :title="__('1. Add products')" flush>
                <x-slot:toolbar>
                    <x-ui.search name="" id="label-search" :placeholder="__('Search product, SKU or barcode...')" />
                </x-slot:toolbar>

                <div class="list-group list-group-flush" id="label-variants" style="max-height: 520px; overflow-y: auto;">
                    @forelse ($variants as $variant)
                        <button type="button" class="list-group-item list-group-item-action label-variant"
                            data-id="{{ $variant->id }}" data-name="{{ $variant->label }}" data-code="{{ $variant->barcode }}"
                            data-stock="{{ $variant->stock }}">
                            <span class="d-flex justify-content-between align-items-center gap-2">
                                <span class="text-truncate">
                                    {{ $variant->label }}
                                    <span class="d-block ui-cell-meta">{{ $variant->barcode }} · {{ $variant->sku }}</span>
                                </span>
                                <x-ui.badge tone="neutral">{{ $variant->stock }}</x-ui.badge>
                            </span>
                        </button>
                    @empty
                        <div class="list-group-item text-muted">{{ __('No product on sale.') }}</div>
                    @endforelse
                </div>
            </x-ui.card>
        </div>

        {{-- Étiquettes à imprimer --}}
        <div class="col-lg-7">
            <form method="POST" action="{{ route('admin.labels.print') }}" target="_blank" id="label-form">
                @csrf
                <x-ui.card :title="__('2. Labels to print')" flush>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Product') }}</th>
                                    <th class="text-end" style="width: 9rem;">{{ __('Labels') }}</th>
                                    <th class="text-end"><span class="visually-hidden">{{ __('Actions') }}</span></th>
                                </tr>
                            </thead>
                            <tbody id="label-lines">
                                @foreach ($selected as $line)
                                    <tr data-id="{{ $line['variant']->id }}">
                                        <td>
                                            {{ $line['variant']->label }}
                                            <div class="ui-cell-meta">{{ $line['variant']->barcode }}</div>
                                        </td>
                                        <td class="text-end">
                                            <input type="number" name="items[{{ $line['variant']->id }}]" value="{{ $line['quantity'] }}"
                                                min="0" max="{{ \App\Modules\Catalog\Services\LabelService::MAX_LABELS }}"
                                                class="form-control form-control-sm text-end label-quantity" aria-label="{{ __('Labels') }}">
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-icon label-remove" title="{{ __('Remove') }}" aria-label="{{ __('Remove') }}">
                                                <i class="bi bi-x-lg" aria-hidden="true"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                                <tr id="label-empty" @class(['d-none' => $selected->isNotEmpty()])>
                                    <td colspan="3" class="text-center text-muted py-4">{{ __('Choose the products in the list.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="card-body border-top">
                        <div class="row g-3">
                            <div class="col-md-7 ui-field mb-0">
                                <label class="form-label" for="label-format">{{ __('Format') }}</label>
                                <select name="format" id="label-format" class="form-select">
                                    @foreach ($formats as $key => $name)
                                        <option value="{{ $key }}" data-per-page="{{ $perPage[$key] }}" @selected(old('format') === $key)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5 ui-field mb-0" id="label-skip-field">
                                <label class="form-label" for="label-skip">{{ __('Labels already used') }}</label>
                                <input type="number" name="skip" id="label-skip" class="form-control" min="0" value="{{ old('skip', 0) }}">
                                <div class="form-text">{{ __('To start a sheet that is already partly used.') }}</div>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-check-inline">
                                    <input type="hidden" name="price" value="0">
                                    <input class="form-check-input" type="checkbox" name="price" value="1" id="label-price" @checked(old('price', true))>
                                    <label class="form-check-label" for="label-price">{{ __('Show the price') }}</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="hidden" name="company" value="0">
                                    <input class="form-check-input" type="checkbox" name="company" value="1" id="label-company" @checked(old('company'))>
                                    <label class="form-check-label" for="label-company">{{ __('Show the shop name') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <x-slot:footer>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <span class="text-muted" id="label-total" aria-live="polite"></span>
                            <button type="submit" class="btn btn-primary" id="label-submit">
                                <i class="bi bi-printer" aria-hidden="true"></i> {{ __('Print the labels') }}
                            </button>
                        </div>
                    </x-slot:footer>
                </x-ui.card>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        const lines = document.getElementById('label-lines');
        const empty = document.getElementById('label-empty');
        const format = document.getElementById('label-format');
        const max = @js(\App\Modules\Catalog\Services\LabelService::MAX_LABELS);
        const i18n = {
            total: @js(__(':labels label(s) · :sheets page(s)')),
            remove: @js(__('Remove')),
            labels: @js(__('Labels')),
        };
        const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

        // Nombre d'étiquettes et de pages ; le bouton n'est actif qu'avec au moins une étiquette
        function refresh() {
            const quantities = [...lines.querySelectorAll('.label-quantity')].map((input) => Math.max(0, parseInt(input.value, 10) || 0));
            const total = quantities.reduce((sum, quantity) => sum + quantity, 0);
            const perPage = Number(format.selectedOptions[0].dataset.perPage);
            const skip = perPage > 1 ? Math.min(Math.max(0, parseInt(document.getElementById('label-skip').value, 10) || 0), perPage - 1) : 0;

            document.getElementById('label-skip-field').classList.toggle('d-none', perPage === 1);
            document.getElementById('label-skip').max = perPage - 1;
            empty.classList.toggle('d-none', quantities.length > 0);
            document.getElementById('label-total').textContent = i18n.total
                .replace(':labels', total)
                .replace(':sheets', total ? Math.ceil((total + skip) / perPage) : 0);
            document.getElementById('label-submit').disabled = total === 0 || total > max;
        }

        // Recherche locale : nom, SKU ou code-barres (un scan trouve la variante)
        document.getElementById('label-search').addEventListener('input', (event) => {
            const value = event.target.value.toLowerCase().trim();
            document.querySelectorAll('.label-variant').forEach((item) => {
                item.classList.toggle('d-none', !item.textContent.toLowerCase().includes(value));
            });
        });

        // Ajout d'une variante : une étiquette par article en stock (au moins une) ; déjà présente, une de plus
        document.getElementById('label-variants').addEventListener('click', (event) => {
            const item = event.target.closest('.label-variant');
            if (!item) return;

            const existing = lines.querySelector(`tr[data-id="${item.dataset.id}"] .label-quantity`);
            if (existing) {
                existing.value = Math.min(max, (parseInt(existing.value, 10) || 0) + 1);
            } else {
                const row = document.createElement('tr');
                row.dataset.id = item.dataset.id;
                row.innerHTML = `
                    <td>${escapeHtml(item.dataset.name)}<div class="ui-cell-meta">${escapeHtml(item.dataset.code)}</div></td>
                    <td class="text-end">
                        <input type="number" name="items[${Number(item.dataset.id)}]" value="${Math.min(max, Math.max(1, Number(item.dataset.stock)))}"
                            min="0" max="${max}" class="form-control form-control-sm text-end label-quantity" aria-label="${escapeHtml(i18n.labels)}">
                    </td>
                    <td class="text-end">
                        <button type="button" class="btn btn-icon label-remove" title="${escapeHtml(i18n.remove)}" aria-label="${escapeHtml(i18n.remove)}">
                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                        </button>
                    </td>`;
                lines.insertBefore(row, empty);
            }
            refresh();
        });

        lines.addEventListener('click', (event) => {
            if (event.target.closest('.label-remove')) {
                event.target.closest('tr').remove();
                refresh();
            }
        });
        document.getElementById('label-form').addEventListener('input', refresh);
        refresh();
    </script>
@endpush
