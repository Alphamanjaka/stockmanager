@extends('layouts.app-back-office')

@section('title', 'Dashboard')
@section('subtitle', 'Overview of the catalog, stock and sales')

@section('content')
    <div class="ui-stack">
        <div class="ui-stats">
            <x-ui.stat label="Products" :value="$totalProducts" icon="bi-box" tone="primary"
                :href="Route::has('admin.products.index') ? route('admin.products.index') : null" />
            <x-ui.stat label="Stock alerts" :value="$lowStockProducts" icon="bi-exclamation-triangle"
                :tone="$lowStockProducts > 0 ? 'danger' : 'success'"
                :hint="$lowStockProducts > 0 ? 'Variants at or below their threshold' : 'No variant below its threshold'"
                :href="Route::has('admin.purchases.createFromShortage') ? route('admin.purchases.createFromShortage') : null" />
            <x-ui.stat label="Categories" :value="$totalCategories" icon="bi-diagram-3" tone="info"
                :href="Route::has('admin.categories.index') ? route('admin.categories.index') : null" />
            <x-ui.stat label="Suppliers" :value="$totalSuppliers" icon="bi-truck" tone="neutral"
                :href="Route::has('admin.suppliers.index') ? route('admin.suppliers.index') : null" />
        </div>

        <div class="row g-3">
            <div class="col-xl-8">
                <x-ui.card title="Sales over time" class="h-100">
                    <x-slot:actions>
                        <select name="period" id="salesChartPeriodSelector" class="form-select form-select-sm" aria-label="Period">
                            <option value="7days" @selected($period == '7days')>Last 7 days</option>
                            <option value="1month" @selected($period == '1month')>Last 4 weeks</option>
                            <option value="1year" @selected($period == '1year')>Last 12 months</option>
                        </select>
                    </x-slot:actions>

                    <div class="position-relative" style="height: 320px;">
                        <div id="chartSpinner" class="d-none position-absolute top-0 start-0 w-100 h-100 justify-content-center align-items-center">
                            <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                        </div>
                        <canvas id="salesChart" role="img" aria-label="Sales over time"></canvas>
                    </div>
                </x-ui.card>
            </div>

            <div class="col-xl-4">
                <x-ui.card title="Business performance" flush class="h-100">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item py-3">
                            <div class="ui-stat-label">Today's sales</div>
                            <div class="ui-stat-value">{{ money($salesToday) }}</div>
                        </li>
                        <li class="list-group-item py-3">
                            <div class="ui-stat-label">This month</div>
                            <div class="ui-stat-value">{{ money($salesThisMonth) }}</div>
                        </li>
                        <li class="list-group-item py-3">
                            <div class="ui-stat-label">Number of sales</div>
                            <div class="ui-stat-value">{{ $totalSales }}</div>
                        </li>
                        <li class="list-group-item py-3">
                            <div class="ui-stat-label">Top selling</div>
                            @if ($mostSoldProduct && $mostSoldProduct->productColor)
                                <div class="fw-semibold mt-1">{{ $mostSoldProduct->productColor->toString() }}</div>
                                <div class="ui-cell-meta">Sold {{ $mostSoldProduct->total_quantity }} times</div>
                            @else
                                <div class="text-muted mt-1">No sales recorded</div>
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
                    label: 'Revenue',
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
                    Swal.fire({ icon: 'error', text: 'Unable to load chart data.' });
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
