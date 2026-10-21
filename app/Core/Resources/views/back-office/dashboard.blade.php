@extends('layouts.app-back-office')

{{--
    Tableau de bord orienté action (DashboardService::getBackOfficeData) : en haut ce qui demande une décision
    (ventes et marge, commandes à recevoir, ruptures à réapprovisionner, stock dormant), puis l'évolution des ventes.
    Les liens sont masqués si le module qui porte la page n'est pas installé.
--}}

@section('title', __('Dashboard'))
@section('subtitle', __('What needs your attention today'))

@php
    $link = fn (string $route, array $parameters = []) => Route::has($route) ? route($route, $parameters) : null;
    $marginHint = ($marginThisMonth['rate'] === null ? __('No sale with a known cost') : __(':rate % of sales', ['rate' => $marginThisMonth['rate']]))
        . ($marginThisMonth['excluded'] > 0 ? ' · ' . __(':count sale(s) without a known cost not counted', ['count' => $marginThisMonth['excluded']]) : '');
@endphp

@section('content')
    <div class="ui-stack">
        <div class="ui-stats">
            <x-ui.stat :label="__('Sales today')" :value="money($salesToday)" icon="bi-calendar-day" tone="success"
                :hint="__(':count sale(s)', ['count' => $salesTodayCount])" :href="$link('admin.sales.index', ['date_from' => today()->toDateString()])" />
            <x-ui.stat :label="__('Gross margin this month')" :value="money($marginThisMonth['margin'])" icon="bi-cash-coin" tone="primary"
                :hint="$marginHint" />
            <x-ui.stat :label="__('Orders to receive')" :value="$awaitingDelivery['count']" icon="bi-truck"
                :tone="$awaitingDelivery['count'] > 0 ? 'info' : 'neutral'"
                :hint="$awaitingDelivery['count'] > 0 ? __(':value still expected', ['value' => money($awaitingDelivery['value'])]) : __('No order in progress')"
                :href="$link('admin.purchases.index')" />
            <x-ui.stat :label="__('To restock')" :value="$shortages['count']" icon="bi-exclamation-triangle"
                :tone="$shortages['count'] > 0 ? 'danger' : 'success'"
                :hint="__(':count variant(s) at or below their threshold, orders in progress included', ['count' => $shortages['below_threshold']])"
                :href="$link('admin.purchases.createFromShortage')" />
            <x-ui.stat :label="__('Dormant stock')" :value="money($dormantStock['value'])" icon="bi-hourglass-bottom"
                :tone="$dormantStock['count'] > 0 ? 'warning' : 'neutral'"
                :hint="__(':count variant(s) without movement for 60 days, at cost', ['count' => $dormantStock['count']])"
                :href="$link('admin.movements.index')" />
        </div>

        <div class="row g-3">
            {{-- Commandes à recevoir : les plus anciennes d'abord --}}
            <div class="col-xl-4">
                <x-ui.card :title="__('Orders to receive')" icon="bi-truck" :count="$awaitingDelivery['count']" flush class="h-100">
                    <ul class="list-group list-group-flush">
                        @forelse ($awaitingDelivery['purchases'] as $purchase)
                            <li class="list-group-item d-flex justify-content-between align-items-start gap-2">
                                <span class="min-w-0">
                                    <a href="{{ route('admin.purchases.show', $purchase->id) }}" class="link-plain fw-semibold">{{ $purchase->reference }}</a>
                                    <span class="ui-cell-meta d-block text-truncate">
                                        {{ $purchase->supplier->name ?? __('documents.not_specified') }} · {{ $purchase->created_at->format('d/m/Y') }}
                                    </span>
                                    <span class="ui-cell-meta d-block">
                                        {{ __(':count item(s) to deliver · :value', ['count' => $purchase->remainingQuantity(), 'value' => money($purchase->remainingValue())]) }}
                                    </span>
                                </span>
                                <span class="d-flex flex-column align-items-end gap-1">
                                    <x-ui.status :value="$purchase->state" />
                                    <a href="{{ route('admin.purchases.receive', $purchase->id) }}" class="btn btn-sm btn-outline-secondary text-nowrap">
                                        <i class="bi bi-box-arrow-in-down" aria-hidden="true"></i> {{ __('Receive') }}
                                    </a>
                                </span>
                            </li>
                        @empty
                            <li class="list-group-item">
                                <x-ui.empty icon="bi-truck">{{ __('No order waiting for a delivery.') }}</x-ui.empty>
                            </li>
                        @endforelse
                    </ul>
                    @if ($awaitingDelivery['count'] > $awaitingDelivery['purchases']->count())
                        <x-slot:footer>
                            <a href="{{ route('admin.purchases.index') }}">{{ __('See all :count orders', ['count' => $awaitingDelivery['count']]) }}</a>
                        </x-slot:footer>
                    @endif
                </x-ui.card>
            </div>

            {{-- Ruptures : même calcul que « Réapprovisionner les ruptures », les plus en manque d'abord --}}
            <div class="col-xl-4">
                <x-ui.card :title="__('To restock')" icon="bi-exclamation-triangle" :count="$shortages['count']" flush class="h-100">
                    <ul class="list-group list-group-flush">
                        @forelse ($shortages['items'] as $shortage)
                            <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                                <span class="min-w-0">
                                    <span class="d-block text-truncate" title="{{ $shortage->label }}">{{ $shortage->label }}</span>
                                    <span class="ui-cell-meta">
                                        {{ __('Threshold :threshold', ['threshold' => $shortage->alert_stock]) }}
                                        @if ($shortage->ordered_quantity > 0)
                                            · {{ __(':count on order', ['count' => $shortage->ordered_quantity]) }}
                                        @endif
                                    </span>
                                </span>
                                <x-ui.badge tone="danger" dot :title="__('Stock')">{{ $shortage->stock }}</x-ui.badge>
                            </li>
                        @empty
                            <li class="list-group-item">
                                <x-ui.empty icon="bi-check2-circle">{{ __('No product requires restocking.') }}</x-ui.empty>
                            </li>
                        @endforelse
                    </ul>
                    @if ($shortages['count'] > 0 && $link('admin.purchases.createFromShortage'))
                        <x-slot:footer>
                            <a href="{{ $link('admin.purchases.createFromShortage') }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-cart-plus" aria-hidden="true"></i> {{ __('Prepare the orders') }}
                            </a>
                        </x-slot:footer>
                    @endif
                </x-ui.card>
            </div>

            {{-- Stock dormant : aucun mouvement depuis 60 jours, plus grosses valeurs au coût d'abord --}}
            <div class="col-xl-4">
                <x-ui.card :title="__('Dormant stock')" icon="bi-hourglass-bottom" :count="$dormantStock['count']" flush class="h-100">
                    <ul class="list-group list-group-flush">
                        @forelse ($dormantStock['variants'] as $variant)
                            <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                                <span class="min-w-0">
                                    <a href="{{ route('admin.products.show', $variant->product_id) }}" class="link-plain d-block text-truncate"
                                        title="{{ $variant->label }}">{{ $variant->label }}</a>
                                    <span class="ui-cell-meta">
                                        {{ $variant->last_movement_at
                                            ? __('Last movement :date', ['date' => $variant->last_movement_at->format('d/m/Y')])
                                            : __('Never moved') }}
                                    </span>
                                </span>
                                <span class="text-end text-nowrap">
                                    <span class="d-block">{{ money($variant->stock * (float) $variant->price_purchase) }}</span>
                                    <span class="ui-cell-meta">{{ __(':count in stock', ['count' => $variant->stock]) }}</span>
                                </span>
                            </li>
                        @empty
                            <li class="list-group-item">
                                <x-ui.empty icon="bi-check2-circle">{{ __('No dormant stock: everything moved in the last 60 days.') }}</x-ui.empty>
                            </li>
                        @endforelse
                    </ul>
                </x-ui.card>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-8">
                <x-ui.card :title="__('Sales over time')" class="h-100">
                    <x-slot:actions>
                        <select name="period" id="salesChartPeriodSelector" class="form-select form-select-sm" aria-label="{{ __('Period') }}">
                            <option value="7days" @selected($period == '7days')>{{ __('Last 7 days') }}</option>
                            <option value="1month" @selected($period == '1month')>{{ __('Last 4 weeks') }}</option>
                            <option value="1year" @selected($period == '1year')>{{ __('Last 12 months') }}</option>
                        </select>
                    </x-slot:actions>

                    <div class="position-relative" style="height: 320px;">
                        <div id="chartSpinner" class="d-none position-absolute top-0 start-0 w-100 h-100 justify-content-center align-items-center">
                            <div class="spinner-border text-primary" role="status"><span class="visually-hidden">{{ __('Loading...') }}</span></div>
                        </div>
                        <canvas id="salesChart" role="img" aria-label="{{ __('Sales over time') }}"></canvas>
                    </div>
                </x-ui.card>
            </div>

            <div class="col-xl-4">
                <x-ui.card :title="__('Business performance')" flush class="h-100">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item py-3">
                            <div class="ui-stat-label">{{ __("This week's sales") }}</div>
                            <div class="ui-stat-value">{{ money($salesThisWeek) }}</div>
                        </li>
                        <li class="list-group-item py-3">
                            <div class="ui-stat-label">{{ __('This month') }}</div>
                            <div class="ui-stat-value">{{ money($salesThisMonth) }}</div>
                        </li>
                        <li class="list-group-item py-3">
                            <div class="ui-stat-label">{{ __('Top selling') }}</div>
                            @if ($mostSoldProduct && $mostSoldProduct->productColor)
                                <div class="fw-semibold mt-1">{{ $mostSoldProduct->productColor->toString() }}</div>
                                <div class="ui-cell-meta">{{ __('Sold :count times', ['count' => $mostSoldProduct->total_quantity]) }}</div>
                            @else
                                <div class="text-muted mt-1">{{ __('No sales recorded') }}</div>
                            @endif
                        </li>
                    </ul>
                </x-ui.card>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        const color = window.appTheme.token('chart-1');
        const currency = {{ Js::from(settings('currency_symbol', 'MGA')) }};
        const format = (value) => `${Number(value).toLocaleString()} ${currency}`;

        const salesChart = new Chart(document.getElementById('salesChart'), {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: {{ Js::from(__('Revenue')) }},
                    data: [],
                    borderColor: color,
                    backgroundColor: window.appTheme.alpha(color, 0.08),
                    fill: true,
                }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (context) => format(context.parsed.y) } },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { maxTicksLimit: 7 } },
                    y: { beginAtZero: true, ticks: { maxTicksLimit: 5, callback: format } },
                },
            },
        });

        // Données du graphique chargées en AJAX selon la période
        function updateSalesChart(period) {
            const spinner = $('#chartSpinner').removeClass('d-none').addClass('d-flex');

            $.ajax({
                url: "{{ route('admin.dashboard.chart-data') }}",
                type: 'GET',
                data: { period },
                success: function (response) {
                    salesChart.data.labels = response.labels;
                    salesChart.data.datasets[0].data = response.values;
                    salesChart.update();
                },
                error: function (xhr) {
                    console.error('Error loading chart data:', xhr.responseText);
                    Swal.fire({ icon: 'error', text: {{ Js::from(__('Unable to load chart data.')) }} });
                },
                complete: () => spinner.addClass('d-none').removeClass('d-flex'),
            });
        }

        $('#salesChartPeriodSelector').on('change', function () {
            updateSalesChart($(this).val());
        });
        updateSalesChart($('#salesChartPeriodSelector').val());
    </script>
@endpush
