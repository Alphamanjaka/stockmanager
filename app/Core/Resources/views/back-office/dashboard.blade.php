@extends('layouts.app-back-office')

@section('title', __('Dashboard'))
@section('subtitle', __('Overview of the catalog, stock and sales'))

@section('content')
    <div class="ui-stack">
        <div class="ui-stats">
            <x-ui.stat :label="__('Products')" :value="$totalProducts" icon="bi-box" tone="primary"
                :href="Route::has('admin.products.index') ? route('admin.products.index') : null" />
            <x-ui.stat :label="__('Stock alerts')" :value="$lowStockProducts" icon="bi-exclamation-triangle"
                :tone="$lowStockProducts > 0 ? 'danger' : 'success'"
                :hint="$lowStockProducts > 0 ? __('Variants at or below their threshold') : __('No variant below its threshold')"
                :href="Route::has('admin.purchases.createFromShortage') ? route('admin.purchases.createFromShortage') : null" />
            <x-ui.stat :label="__('Categories')" :value="$totalCategories" icon="bi-diagram-3" tone="info"
                :href="Route::has('admin.categories.index') ? route('admin.categories.index') : null" />
            <x-ui.stat :label="__('Suppliers')" :value="$totalSuppliers" icon="bi-truck" tone="neutral"
                :href="Route::has('admin.suppliers.index') ? route('admin.suppliers.index') : null" />
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
                            <div class="ui-stat-label">{{ __('Gross margin this month') }}</div>
                            <div class="ui-stat-value">{{ money($marginThisMonth['margin']) }}</div>
                            <div class="ui-cell-meta">
                                {{ $marginThisMonth['rate'] === null ? __('No sale with a known cost') : __(':rate % of sales', ['rate' => $marginThisMonth['rate']]) }}
                                @if ($marginThisMonth['excluded'] > 0)
                                    · {{ __(':count sale(s) without a known cost not counted', ['count' => $marginThisMonth['excluded']]) }}
                                @endif
                            </div>
                        </li>
                        <li class="list-group-item py-3">
                            <div class="ui-stat-label">{{ __('Number of sales') }}</div>
                            <div class="ui-stat-value">{{ $totalSales }}</div>
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
