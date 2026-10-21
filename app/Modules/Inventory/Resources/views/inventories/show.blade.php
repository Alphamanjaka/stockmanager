@extends('layouts.app-back-office')

@section('title', __('Stock count :reference', ['reference' => $inventory->reference]))
@section('subtitle', $inventory->category->name ?? __('All products'))

@php
    $counted = $lines->filter->isCounted();
    $gaps = $counted->filter(fn ($line) => $line->gap() !== 0);
    $draft = $inventory->isDraft();
@endphp

@section('content')
    <div class="ui-stack">
        <x-ui.errors />

        <div class="ui-stats">
            @if ($draft)
                <x-ui.stat :label="__('Status')" :value="__('Draft')" icon="bi-pencil-square" tone="warning" :hint="__('Counting in progress')" />
            @else
                <x-ui.stat :label="__('Status')" :value="__('Validated')" icon="bi-check-circle" tone="success"
                    :hint="$inventory->validated_at?->format('d/m/Y H:i') . ' — ' . ($inventory->validator->name ?? '')" />
            @endif
            <x-ui.stat :label="__('Counted lines')" :value="$counted->count() . ' / ' . $lines->count()" icon="bi-list-check" tone="primary" />
            <x-ui.stat :label="__('Lines with a gap')" :value="$gaps->count()" icon="bi-exclamation-diamond"
                :tone="$gaps->isNotEmpty() ? 'danger' : 'success'" />
        </div>

        @if ($inventory->notes)
            <x-ui.alert tone="secondary" icon="bi-sticky"><span class="text-prewrap">{{ $inventory->notes }}</span></x-ui.alert>
        @endif

        @if ($draft)
            <form method="POST" action="{{ route('admin.inventories.update', $inventory->id) }}" id="count-form">
                @csrf
                @method('PUT')
        @endif

        <x-ui.card flush>
            <x-slot:toolbar>
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="flex-grow-1" style="max-width: 420px">
                        <x-ui.search name="" id="line-filter" :placeholder="__('Filter by product, variant or SKU...')" />
                    </div>
                    @if ($draft)
                        <span class="small text-muted">
                            {{ __('Leave a line empty to keep its stock unchanged. Save as you count: each gap is measured against the stock when its line is saved, and sales made afterwards are kept.') }}
                        </span>
                    @endif
                </div>
            </x-slot:toolbar>

            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('Variant') }}</th>
                            <th class="text-end">{{ __('Expected') }}</th>
                            @if ($draft)
                                <th class="text-end">{{ __('Current stock') }}</th>
                                <th class="text-end" style="width: 140px">{{ __('Counted') }}</th>
                            @else
                                <th class="text-end">{{ __('When counted') }}</th>
                                <th class="text-end">{{ __('Counted') }}</th>
                            @endif
                            <th class="text-end">{{ __('Gap') }}</th>
                            @unless ($draft)
                                <th class="text-end">{{ __('Stock at validation') }}</th>
                            @endunless
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lines as $line)
                            @php $gap = $line->gap(); @endphp
                            <tr class="inventory-line">
                                <td class="fw-semibold">{{ $line->productColor->product->name ?? '—' }}</td>
                                <td>
                                    {{ $line->productColor->variant_name ?: '—' }}
                                    <div class="ui-cell-meta">{{ $line->productColor->sku }}</div>
                                </td>
                                <td class="text-end">{{ $line->expected_quantity }}</td>
                                @if ($draft)
                                    <td class="text-end">
                                        {{ $line->productColor->stock }}
                                        @if ($line->isCounted() && $line->stock_at_count !== null && $line->productColor->stock !== $line->stock_at_count)
                                            <i class="bi bi-info-circle text-info"
                                                title="{{ __('Stock when counted: :stock. The sales and receipts made since then are kept.', ['stock' => $line->stock_at_count]) }}"></i>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <input type="number" min="0" step="1" name="counts[{{ $line->id }}]"
                                            value="{{ old('counts.' . $line->id, $line->counted_quantity) }}"
                                            class="form-control form-control-sm text-end" aria-label="{{ __('Counted quantity') }}">
                                    </td>
                                @else
                                    <td class="text-end">{{ $line->stock_at_count ?? $line->stock_at_validation ?? '—' }}</td>
                                    <td class="text-end">{{ $line->counted_quantity ?? __('not counted') }}</td>
                                @endif
                                <td @class(['text-end fw-semibold', 'text-success' => $gap > 0, 'text-danger' => $gap < 0, 'text-muted' => !$gap])>
                                    {{ $gap === null ? '' : ($gap > 0 ? '+' . $gap : $gap) }}
                                </td>
                                @unless ($draft)
                                    <td class="text-end text-nowrap">
                                        {{ $line->stockAfterValidation() === null ? '—' : $line->stock_at_validation . ' → ' . $line->stockAfterValidation() }}
                                    </td>
                                @endunless
                            </tr>
                        @empty
                            <x-ui.empty-row :colspan="$draft ? 6 : 7" icon="bi-box">{{ __('No variant in this scope.') }}</x-ui.empty-row>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        @if ($draft)
                <div class="ui-form-bar ui-form-actions">
                    <button type="submit" class="btn btn-link text-danger ui-form-actions-start" form="delete-inventory-form">
                        <i class="bi bi-trash3" aria-hidden="true"></i> {{ __('Delete this draft') }}
                    </button>
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="bi bi-floppy" aria-hidden="true"></i> {{ __('Save counts') }}
                    </button>
                    <button type="submit" class="btn btn-primary" formaction="{{ route('admin.inventories.validate', $inventory->id) }}"
                        data-confirm="{{ __('Validate this stock count?') }}" data-confirm-ok="{{ __('Validate') }}"
                        data-confirm-text="{{ __('The gap of every counted line (counted − stock when counted) will be applied to the current stock. This cannot be undone.') }}">
                        <i class="bi bi-check2-circle" aria-hidden="true"></i> {{ __('Validate and adjust stock') }}
                    </button>
                </div>
            </form>

            <form method="POST" action="{{ route('admin.inventories.destroy', $inventory->id) }}" id="delete-inventory-form"
                data-confirm="{{ __('Delete this draft stock count?') }}" data-confirm-tone="danger" data-confirm-ok="{{ __('Delete') }}">
                @csrf
                @method('DELETE')
            </form>
        @endif
    </div>

    @push('scripts')
        <script>
            // Filtre local des lignes (produit, variante ou SKU)
            document.getElementById('line-filter').addEventListener('input', function () {
                const term = this.value.toLowerCase();
                document.querySelectorAll('.inventory-line').forEach(function (row) {
                    row.hidden = !row.textContent.toLowerCase().includes(term);
                });
            });
        </script>
    @endpush
@endsection
