@extends('layouts.app-back-office')

@section('title', __('Stock movements'))
@section('subtitle', __('Every stock change: received purchases, sales, stock counts and corrections.'))

@section('actions')
    <x-ui.actions-menu>
        <x-ui.export-items list="movements" />
        <li><hr class="dropdown-divider"></li>
        {{-- Secours en cas d'erreur de saisie : volontairement rangé dans le menu --}}
        <x-ui.menu-item :href="route('admin.movements.create')" icon="bi-arrow-left-right">{{ __('Stock correction') }}</x-ui.menu-item>
    </x-ui.actions-menu>
@endsection

@section('content')
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#list-pane" type="button"
                role="tab" aria-controls="list-pane" aria-selected="true">
                <i class="bi bi-list-ul" aria-hidden="true"></i> {{ __('History') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats-pane" type="button"
                role="tab" aria-controls="stats-pane" aria-selected="false">
                <i class="bi bi-graph-up" aria-hidden="true"></i> {{ __('Statistics') }}
            </button>
        </li>
    </ul>

    <div class="tab-content">
        {{-- Historique --}}
        <div class="tab-pane fade show active" id="list-pane" role="tabpanel" aria-labelledby="list-tab" tabindex="0">
            <x-ui.card flush :pagination="$stockMovements" data-bulk>
                <x-slot:toolbar>
                    <x-ui.filters :action="route('admin.movements.index')">
                        <x-ui.search :placeholder="__('Product, reason...')" />
                        <select name="type" class="form-select ui-filter" aria-label="{{ __('Type') }}">
                            <option value="">{{ __('All types') }}</option>
                            <option value="in" @selected(request('type') == 'in')>{{ __('Stock in') }}</option>
                            <option value="out" @selected(request('type') == 'out')>{{ __('Stock out') }}</option>
                        </select>
                        <div class="input-group ui-filter">
                            <span class="input-group-text">{{ __('From') }}</span>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" aria-label="{{ __('From') }}">
                            <span class="input-group-text">{{ __('To') }}</span>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" aria-label="{{ __('To') }}">
                        </div>
                    </x-ui.filters>
                </x-slot:toolbar>

                <x-ui.bulk-bar export="movements" />

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <x-ui.bulk-check-all />
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Product') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th class="text-end">{{ __('Quantity') }}</th>
                                <th class="text-end">{{ __('Stock before → after') }}</th>
                                <th>{{ __('Reason') }}</th>
                                <th>{{ __('By') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stockMovements as $movement)
                                @php
                                    // Nature du mouvement d'après sa source (correction, inventaire, vente, achat)
                                    [$tone, $label] = $movement->kind();
                                    $isLowStock = $movement->stock_after <= ($movement->productColor->alert_stock ?? 0);
                                @endphp
                                <tr>
                                    <x-ui.bulk-check :value="$movement->id" />
                                    <td class="text-nowrap">
                                        {{ $movement->created_at->format('d/m/Y') }}
                                        <div class="ui-cell-meta">{{ $movement->created_at->format('H:i') }}</div>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.movements.show', $movement->id) }}" class="link-plain">
                                            {{ $movement->productColor->toString() }}
                                        </a>
                                        @if ($isLowStock)
                                            <i class="bi bi-exclamation-triangle-fill text-danger ms-1" title="{{ __('Low stock') }}" aria-label="{{ __('Low stock') }}"></i>
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
                                    <td class="text-nowrap">{{ $movement->user->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <x-ui.empty-row :colspan="8" icon="bi-arrow-left-right">{{ __('No movement found.') }}</x-ui.empty-row>
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
                    <x-ui.card :title="__('Stock value')" :subtitle="__('Last 30 days, at purchase cost')" class="h-100">
                        @if ($variantsWithoutCost > 0)
                            <p class="small text-muted">
                                <i class="bi bi-info-circle" aria-hidden="true"></i>
                                {{ __(':count variant(s) in stock have no purchase price and count for 0.', ['count' => $variantsWithoutCost]) }}
                            </p>
                        @endif
                        <div style="height: 300px;">
                            <canvas id="stockValueChart" role="img" aria-label="{{ __('Stock value over the last 30 days') }}"></canvas>
                        </div>
                    </x-ui.card>
                </div>

                <div class="col-lg-4">
                    <div class="ui-stack h-100">
                        <x-ui.card :title="__('Dormant products')" :count="$dormantStock['count']"
                            :subtitle="__('No movement for more than 60 days · :value at cost', ['value' => money($dormantStock['value'])])" flush>
                            <ul class="list-group list-group-flush" style="max-height: 180px; overflow-y: auto;">
                                @forelse ($dormantStock['variants'] as $variant)
                                    <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                                        <span class="text-truncate" title="{{ $variant->label }}">{{ $variant->label }}</span>
                                        <x-ui.badge tone="neutral">{{ $variant->stock }}</x-ui.badge>
                                    </li>
                                @empty
                                    <li class="list-group-item text-muted">{{ __('Nothing to report.') }}</li>
                                @endforelse
                            </ul>
                        </x-ui.card>

                        <x-ui.card :title="__('Top rotation')" :subtitle="__('Most frequent stock outs')" flush>
                            <ul class="list-group list-group-flush" style="max-height: 180px; overflow-y: auto;">
                                @forelse ($rotationStats as $stat)
                                    <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                                        <span class="text-truncate" title="{{ $stat->productColor->label }}">{{ $stat->productColor->label }}</span>
                                        <x-ui.badge tone="primary">-{{ $stat->total_out }}</x-ui.badge>
                                    </li>
                                @empty
                                    <li class="list-group-item text-muted">{{ __('Not enough data.') }}</li>
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
                    label: @js(__('Total value')),
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
