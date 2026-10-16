@extends('layouts.app-back-office')

@section('title', 'Stock movements')
@section('subtitle', 'Every stock change: received purchases, sales, stock counts and corrections.')

@section('actions')
    {{-- Secours en cas d'erreur de saisie : volontairement en bouton secondaire --}}
    <a href="{{ route('admin.movements.create') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left-right" aria-hidden="true"></i> Stock correction
    </a>
@endsection

@section('content')
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#list-pane" type="button"
                role="tab" aria-controls="list-pane" aria-selected="true">
                <i class="bi bi-list-ul" aria-hidden="true"></i> History
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats-pane" type="button"
                role="tab" aria-controls="stats-pane" aria-selected="false">
                <i class="bi bi-graph-up" aria-hidden="true"></i> Statistics
            </button>
        </li>
    </ul>

    <div class="tab-content">
        {{-- Historique --}}
        <div class="tab-pane fade show active" id="list-pane" role="tabpanel" aria-labelledby="list-tab" tabindex="0">
            <x-ui.card flush :pagination="$stockMovements">
                <x-slot:toolbar>
                    <x-ui.filters :action="route('admin.movements.index')">
                        <x-ui.search placeholder="Product, reason..." />
                        <select name="type" class="form-select ui-filter" aria-label="Type">
                            <option value="">All types</option>
                            <option value="in" @selected(request('type') == 'in')>Stock in</option>
                            <option value="out" @selected(request('type') == 'out')>Stock out</option>
                        </select>
                        <div class="input-group ui-filter">
                            <span class="input-group-text">From</span>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" aria-label="From">
                            <span class="input-group-text">To</span>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" aria-label="To">
                        </div>
                    </x-ui.filters>
                </x-slot:toolbar>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Type</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Stock before → after</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stockMovements as $movement)
                                @php
                                    // Nature du mouvement déduite du motif (correction, inventaire, vente, achat)
                                    $reason = strtolower((string) $movement->reason);
                                    if (str_starts_with($reason, 'correction')) {
                                        [$tone, $label] = ['warning', 'Correction'];
                                    } elseif (str_starts_with($reason, 'inventaire')) {
                                        [$tone, $label] = ['info', 'Stock count'];
                                    } elseif (str_contains($reason, 'ajustement') || str_contains($reason, 'manuel')) {
                                        [$tone, $label] = ['info', 'Adjustment'];
                                    } elseif ($movement->type === 'in') {
                                        [$tone, $label] = ['success', str_contains($reason, 'achat') || str_contains($reason, 'pur') ? 'Purchase' : 'Stock in'];
                                    } elseif ($movement->type === 'out') {
                                        [$tone, $label] = ['danger', str_contains($reason, 'vente') || str_contains($reason, 'sale') ? 'Sale' : 'Stock out'];
                                    } else {
                                        [$tone, $label] = ['neutral', 'Other'];
                                    }
                                    $isLowStock = $movement->stock_after <= ($movement->productColor->alert_stock ?? 0);
                                @endphp
                                <tr>
                                    <td class="text-nowrap">
                                        {{ $movement->created_at->format('d/m/Y') }}
                                        <div class="ui-cell-meta">{{ $movement->created_at->format('H:i') }}</div>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.movements.show', $movement->id) }}" class="link-plain">
                                            {{ $movement->productColor->toString() }}
                                        </a>
                                        @if ($isLowStock)
                                            <i class="bi bi-exclamation-triangle-fill text-danger ms-1" title="Low stock" aria-label="Low stock"></i>
                                        @endif
                                    </td>
                                    <td><x-ui.badge :tone="$tone" dot>{{ $label }}</x-ui.badge></td>
                                    <td @class(['text-end fw-semibold', 'text-success' => $movement->type === 'in', 'text-danger' => $movement->type !== 'in'])>
                                        {{ $movement->type === 'in' ? '+' : '-' }}{{ abs($movement->quantity) }}
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <span class="text-muted">{{ $movement->stock_before }}</span>
                                        <i class="bi bi-arrow-right text-subtle mx-1" aria-hidden="true"></i>
                                        <span class="fw-semibold">{{ $movement->stock_after }}</span>
                                    </td>
                                    <td class="text-muted text-truncate" style="max-width: 240px;" title="{{ $movement->reason }}">
                                        {{ $movement->reason }}
                                    </td>
                                </tr>
                            @empty
                                <x-ui.empty-row :colspan="6" icon="bi-arrow-left-right">No movement found.</x-ui.empty-row>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>

        {{-- Statistiques --}}
        <div class="tab-pane fade" id="stats-pane" role="tabpanel" aria-labelledby="stats-tab" tabindex="0">
            <div class="row g-3">
                <div class="col-lg-8">
                    <x-ui.card title="Stock value" subtitle="Last 30 days" class="h-100">
                        <div style="height: 300px;">
                            <canvas id="stockValueChart" role="img" aria-label="Stock value over the last 30 days"></canvas>
                        </div>
                    </x-ui.card>
                </div>

                <div class="col-lg-4">
                    <div class="ui-stack h-100">
                        <x-ui.card title="Dormant products" subtitle="No movement for more than 60 days" flush>
                            <ul class="list-group list-group-flush" style="max-height: 180px; overflow-y: auto;">
                                @forelse ($dormantProducts as $product)
                                    <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                                        <span class="text-truncate">{{ $product->name }}</span>
                                        <x-ui.badge tone="neutral">{{ $product->quantity_stock }}</x-ui.badge>
                                    </li>
                                @empty
                                    <li class="list-group-item text-muted">Nothing to report.</li>
                                @endforelse
                            </ul>
                        </x-ui.card>

                        <x-ui.card title="Top rotation" subtitle="Most frequent stock outs" flush>
                            <ul class="list-group list-group-flush" style="max-height: 180px; overflow-y: auto;">
                                @forelse ($rotationStats as $stat)
                                    <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                                        <span class="text-truncate" title="{{ $stat->productColor->label }}">{{ $stat->productColor->label }}</span>
                                        <x-ui.badge tone="primary">-{{ $stat->total_out }}</x-ui.badge>
                                    </li>
                                @empty
                                    <li class="list-group-item text-muted">Not enough data.</li>
                                @endforelse
                            </ul>
                        </x-ui.card>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        const data = @json($stockValueEvolution);
        const color = window.appTheme.token('chart-1');
        const currency = {{ Js::from(settings('currency_symbol', 'MGA')) }};

        new Chart(document.getElementById('stockValueChart'), {
            type: 'line',
            data: {
                labels: data.map(item => item.date),
                datasets: [{
                    label: 'Total value',
                    data: data.map(item => item.value),
                    borderColor: color,
                    backgroundColor: window.appTheme.alpha(color, 0.08),
                    fill: true,
                }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.parsed.y.toLocaleString()} ${currency}`,
                        },
                    },
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: false },
                },
            },
        });
    </script>
@endpush
