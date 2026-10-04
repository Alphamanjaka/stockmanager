@extends('layouts.app-back-office')

@section('title', 'Stock Count ' . $inventory->reference)
@section('actions')
    <a href="{{ route('admin.inventories.index') }}" class="btn btn-outline-secondary">Back</a>
@endsection

@php
    $counted = $lines->filter->isCounted();
    $gaps = $counted->filter(fn ($line) => $line->gap() !== 0);
@endphp

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm"><div class="card-body">
                <div class="text-muted small">Status</div>
                @if ($inventory->isDraft())
                    <span class="badge bg-warning text-dark fs-6">Draft</span>
                @else
                    <span class="badge bg-success fs-6">Validated</span>
                    <div class="small text-muted mt-1">{{ $inventory->validated_at?->format('d/m/Y H:i') }} — {{ $inventory->validator->name ?? '' }}</div>
                @endif
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm"><div class="card-body">
                <div class="text-muted small">Scope</div>
                <div class="fw-bold">{{ $inventory->category->name ?? 'All products' }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm"><div class="card-body">
                <div class="text-muted small">Counted lines</div>
                <div class="fw-bold">{{ $counted->count() }} / {{ $lines->count() }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm"><div class="card-body">
                <div class="text-muted small">Lines with a gap</div>
                <div class="fw-bold {{ $gaps->isNotEmpty() ? 'text-danger' : 'text-success' }}">{{ $gaps->count() }}</div>
            </div></div>
        </div>
    </div>

    @if ($inventory->notes)
        <div class="alert alert-light border" style="white-space: pre-line">{{ $inventory->notes }}</div>
    @endif

    @if ($inventory->isDraft())
        <form method="POST" action="{{ route('admin.inventories.update', $inventory->id) }}" id="count-form">
            @csrf
            @method('PUT')
    @endif

    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <input type="search" id="line-filter" class="form-control form-control-sm w-50" placeholder="Filter by product or color...">
            @if ($inventory->isDraft())
                <span class="small text-muted">Leave a line empty to keep its stock unchanged.</span>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th>Color</th>
                        <th class="text-end">Expected</th>
                        @if ($inventory->isDraft())
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
                            <td>{{ $line->productColor->product->name ?? '—' }}</td>
                            <td>{{ $line->productColor->color->name ?? '—' }}</td>
                            <td class="text-end">{{ $line->expected_quantity }}</td>
                            @if ($inventory->isDraft())
                                <td class="text-end">
                                    {{ $line->productColor->stock }}
                                    @if ($line->productColor->stock !== $line->expected_quantity)
                                        <i class="bi bi-exclamation-circle text-warning" title="The stock moved since the count was opened"></i>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <input type="number" min="0" step="1" name="counts[{{ $line->id }}]"
                                        value="{{ old('counts.' . $line->id, $line->counted_quantity) }}"
                                        class="form-control form-control-sm text-end">
                                </td>
                            @else
                                <td class="text-end">{{ $line->stock_at_validation ?? '—' }}</td>
                                <td class="text-end">{{ $line->counted_quantity ?? 'not counted' }}</td>
                            @endif
                            <td class="text-end fw-bold {{ $gap > 0 ? 'text-success' : ($gap < 0 ? 'text-danger' : 'text-muted') }}">
                                {{ $gap === null ? '' : ($gap > 0 ? '+' . $gap : $gap) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No variant in this scope.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($inventory->isDraft())
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-save"></i> Save counts
                </button>
                <button type="submit" class="btn btn-success" formaction="{{ route('admin.inventories.validate', $inventory->id) }}"
                    onclick="return confirm('Validate this stock count? The stock of every counted line will be set to the counted quantity. This cannot be undone.');">
                    <i class="bi bi-check2-circle"></i> Validate and adjust stock
                </button>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.inventories.destroy', $inventory->id) }}" class="mt-3"
            onsubmit="return confirm('Delete this draft stock count?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-link text-danger p-0">Delete this draft</button>
        </form>
    @endif

    @push('scripts')
        <script>
            // Filtre local des lignes (produit ou couleur)
            document.getElementById('line-filter').addEventListener('input', function () {
                const term = this.value.toLowerCase();
                document.querySelectorAll('.inventory-line').forEach(function (row) {
                    row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
                });
            });
        </script>
    @endpush
@endsection
