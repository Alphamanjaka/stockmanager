{{--
    Bon de commande, commun à la création et à la modification d'un brouillon.
    Comportement (recherche au clavier, lignes, coûts proposés, ruptures, totaux, contrôles) : resources/js/purchase-form.js.
    Variables : $purchase (null en création), $suppliers, $formData (catalogue, ruptures, lignes de départ, devise).
--}}
@php
    $supplierId = old('supplier_id', $purchase?->supplier_id);
    $currency = $formData['currency'];
@endphp

<form method="POST" id="purchase-form" novalidate
    action="{{ $purchase ? route('admin.purchases.update', $purchase->id) : route('admin.purchases.store') }}">
    @csrf
    @if ($purchase)
        @method('PUT')
    @endif

    <div class="ui-stack">
        <x-ui.errors />

        {{-- 1. En-tête --}}
        <x-ui.card :title="__('Supplier')">
            <div class="row">
                <x-ui.field class="col-md-6" name="supplier_id" :label="__('Supplier')" required>
                    <select name="supplier_id" id="supplier_id" data-placeholder="{{ __('Choose a supplier...') }}"
                        @class(['form-select', 'is-invalid' => $errors->has('supplier_id')])>
                        <option value=""></option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected($supplierId == $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </x-ui.field>
                <x-ui.field class="col-md-6" name="supplier_reference" :label="__('Supplier reference')" maxlength="100"
                    :placeholder="__('Invoice or delivery note number')" :hint="__('Optional: the number on the supplier\'s document.')"
                    :value="old('supplier_reference', $purchase?->supplier_reference)" />
                <x-ui.field class="col-12 mb-0" name="notes" :label="__('Notes')" type="textarea" rows="2" maxlength="2000"
                    :placeholder="__('Delivery date, payment terms...')" :value="old('notes', $purchase?->notes)" />
            </div>
        </x-ui.card>

        {{-- 2. Lignes --}}
        <x-ui.card :title="__('Lines')" flush :subtitle="__('Cost proposed: last price paid to this supplier, otherwise the purchase price of the variant.')">
            <x-slot:actions>
                <span class="ui-tag">{!! __(':count line(s)', ['count' => '<span id="line-count">0</span>']) !!}</span>
            </x-slot:actions>

            <x-slot:toolbar>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="ui-combobox flex-grow-1" style="max-width: 560px">
                        <div class="ui-search">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input type="text" id="product-search" class="form-control" autocomplete="off" spellcheck="false"
                                placeholder="{{ __('Add a product: name or SKU (F2), then Enter') }}" aria-label="{{ __('Add a product') }}"
                                role="combobox" aria-expanded="false" aria-controls="product-results" aria-autocomplete="list">
                        </div>
                        <div id="product-results" class="list-group ui-combobox-list" role="listbox" hidden></div>
                    </div>
                    <button type="button" class="btn btn-outline-secondary" id="add-shortages" disabled
                        title="{{ __('Add the products of this supplier that are at or below their alert threshold') }}">
                        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i> {{ __('Add shortages') }}
                        <span class="ui-tag" id="shortage-count">0</span>
                    </button>
                </div>
            </x-slot:toolbar>

            <div class="table-responsive">
                <table class="table" id="purchase-lines-table">
                    <thead>
                        <tr>
                            <th>{{ __('Product') }}</th>
                            <th class="text-end">{{ __('Stock') }}</th>
                            <th class="text-end" style="width: 120px">{{ __('Quantity') }}</th>
                            <th class="text-end" style="width: 190px">{{ __('Unit cost') }}</th>
                            <th class="text-end" style="width: 160px">{{ __('Subtotal') }}</th>
                            <th style="width: 48px"><span class="visually-hidden">{{ __('Remove') }}</span></th>
                        </tr>
                    </thead>
                    <tbody id="purchase-lines"></tbody>
                </table>
            </div>
            <x-ui.empty id="lines-empty" icon="bi-cart3" :title="__('No line yet')">
                {{ __('Search a product above, or add the shortages of the supplier.') }}
            </x-ui.empty>

            <x-slot:footer>
                <div class="ui-totals py-1">
                    <div><span class="text-muted">{{ __('Subtotal') }}</span><span id="total-gross">—</span></div>
                    <div class="align-items-center">
                        <label for="discount" class="text-muted">{{ __('Discount') }}</label>
                        <div class="input-group input-group-sm" style="max-width: 190px">
                            <input type="number" name="discount" id="discount" min="0" step="0.01" placeholder="0"
                                value="{{ old('discount', $purchase && $purchase->discount > 0 ? (float) $purchase->discount : '') }}"
                                @class(['form-control text-end', 'is-invalid' => $errors->has('discount')])>
                            <span class="input-group-text">{{ $currency }}</span>
                        </div>
                    </div>
                    <div class="ui-totals-grand"><span>{{ __('Net total') }}</span><span id="total-net">—</span></div>
                </div>
            </x-slot:footer>
        </x-ui.card>
    </div>

    {{-- 3. Actions : le stock n'augmente qu'à la réception --}}
    <div class="ui-form-bar ui-form-actions">
        <span class="small text-muted ui-form-actions-start">
            <i class="bi bi-info-circle" aria-hidden="true"></i> {{ __('Stock increases only when the purchase is received.') }}
        </span>
        <a href="{{ $purchase ? route('admin.purchases.show', $purchase->id) : route('admin.purchases.index') }}"
            class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        <button type="submit" name="action" value="draft" class="btn btn-outline-secondary">
            <i class="bi bi-floppy" aria-hidden="true"></i> {{ __('Save draft') }}
        </button>
        <button type="submit" name="action" value="ordered" class="btn btn-outline-primary">
            <i class="bi bi-send" aria-hidden="true"></i> {{ __('Mark as ordered') }}
        </button>
        <button type="submit" name="action" value="received" class="btn btn-primary"
            data-confirm="{{ __('Receive this purchase now?') }}" data-confirm-ok="{{ __('Receive') }}"
            data-confirm-text="{{ __('The quantities of every line are added to the stock. This cannot be undone.') }}">
            <i class="bi bi-box-arrow-in-down" aria-hidden="true"></i> {{ __('Receive now') }}
        </button>
    </div>
</form>

@php
    // Textes du script (resources/js/purchase-form.js), dans la langue de l'application.
    // Construits ici : la directive json découpe son argument aux virgules.
    $formData['i18n'] = [
        'alertThreshold' => __('Alert threshold: :count'),
        'quantity' => __('Quantity'),
        'unitCost' => __('Unit cost'),
        'remove' => __('Remove'),
        'inList' => __('In the list'),
        'stock' => __('Stock :count'),
        'noProduct' => __('No product found'),
        'linesAdded' => __(':count line(s) added'),
        'chooseSupplier' => __('Choose the supplier.'),
        'addLine' => __('Add at least one product line.'),
        'wholeQuantity' => __('Each quantity must be a whole number of at least 1.'),
        'unitCostRequired' => __('Each line needs a unit cost (0 or more).'),
        'discountTooHigh' => __('The discount cannot exceed the subtotal.'),
    ];
@endphp
<script type="application/json" id="purchase-form-data">@json($formData)</script>

@push('scripts')
    @vite('resources/js/purchase-form.js')
@endpush
