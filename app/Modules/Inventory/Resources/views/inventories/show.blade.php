@extends('layouts.app-back-office')

@section('title', 'Stock count ' . $inventory->reference)
@section('subtitle', $inventory->category->name ?? 'All products')

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
                <x-ui.stat label="Status" value="Draft" icon="bi-pencil-square" tone="warning" hint="Counting in progress" />
            @else
                <x-ui.stat label="Status" value="Validated" icon="bi-check-circle" tone="success"
                    :hint="$inventory->validated_at?->format('d/m/Y H:i') . ' — ' . ($inventory->validator->name ?? '')" />
            @endif
            <x-ui.stat label="Counted lines" :value="$counted->count() . ' / ' . $lines->count()" icon="bi-list-check" tone="primary" />
            <x-ui.stat label="Lines with a gap" :value="$gaps->count()" icon="bi-exclamation-diamond"
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
                        <x-ui.search name="" id="line-filter" placeholder="Filter by product, variant or SKU..." />
                    </div>
                    @if ($draft)
                        <span class="small text-muted">Leave a line empty to keep its stock unchanged.</span>
                    @endif
                </div>
            </x-slot:toolbar>

            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Variant</th>
                            <th class="text-end">Expected</th>
                            @if ($draft)
                                <th class="text-end">Current stock</th>
                                <th class="text-end" style="width: 140px">Counted</th>
                            @else
                                <th class="text-end">Stock before</th>
                                <th class="text-end">Counted</th>
                            @endif
                            <th class="text-end">Gap</th>
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
                                        @if ($line->productColor->stock !== $line->expected_quantity)
                                            <i class="bi bi-exclamation-circle text-warning" title="The stock moved since the count was opened"></i>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <input type="number" min="0" step="1" name="counts[{{ $line->id }}]"
                                            value="{{ old('counts.' . $line->id, $line->counted_quantity) }}"
                                            class="form-control form-control-sm text-end" aria-label="Counted quantity">
                                    </td>
                                @else
                                    <td class="text-end">{{ $line->stock_at_validation ?? '—' }}</td>
                                    <td class="text-end">{{ $line->counted_quantity ?? 'not counted' }}</td>
                                @endif
                                <td @class(['text-end fw-semibold', 'text-success' => $gap > 0, 'text-danger' => $gap < 0, 'text-muted' => !$gap])>
                                    {{ $gap === null ? '' : ($gap > 0 ? '+' . $gap : $gap) }}
                                </td>
                            </tr>
                        @empty
                            <x-ui.empty-row :colspan="6" icon="bi-box">No variant in this scope.</x-ui.empty-row>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        @if ($draft)
                <div class="ui-form-bar ui-form-actions">
                    <button type="submit" class="btn btn-link text-danger ui-form-actions-start" form="delete-inventory-form">
                        <i class="bi bi-trash3" aria-hidden="true"></i> Delete this draft
                    </button>
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="bi bi-floppy" aria-hidden="true"></i> Save counts
                    </button>
                    <button type="submit" class="btn btn-primary" formaction="{{ route('admin.inventories.validate', $inventory->id) }}"
                        data-confirm="Validate this stock count?" data-confirm-ok="Validate"
                        data-confirm-text="The stock of every counted line will be set to the counted quantity. This cannot be undone.">
                        <i class="bi bi-check2-circle" aria-hidden="true"></i> Validate and adjust stock
                    </button>
                </div>
            </form>

            <form method="POST" action="{{ route('admin.inventories.destroy', $inventory->id) }}" id="delete-inventory-form"
                data-confirm="Delete this draft stock count?" data-confirm-tone="danger" data-confirm-ok="Delete">
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
