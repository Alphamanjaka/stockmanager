@extends('layouts.app-back-office')

@section('title', 'Back Office - Products Dashboard')
@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
    </div>

    <!-- line 1 : Indicator key (KPIs) -->
    <div class="row mb-4">
        <!--Total Products -->
        <div class="col-md-3 mb-3">
            <div class="card border-start border-4 border-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Products</div>
                            <div class="h5 mb-0 font-weight-bold text-dark">{{ $totalProducts }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-box fa-2x text-gray-300 text-secondary opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Bas (Alert) -->
        <div class="col-md-3 mb-3">
            <div class="card border-start border-4 border-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Stock Alert</div>
                            <div class="h5 mb-0 font-weight-bold text-dark">{{ $lowStockProducts }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-secondary opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories -->
        <div class="col-md-3 mb-3">
            <div class="card border-start border-4 border-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Categories</div>
                            <div class="h5 mb-0 font-weight-bold text-dark">{{ $totalCategories }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tags fa-2x text-secondary opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Suppliers -->
        <div class="col-md-3 mb-3">
            <div class="card border-start border-4 border-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Suppliers</div>
                            <div class="h5 mb-0 font-weight-bold text-dark">{{ $totalSuppliers }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-truck fa-2x text-secondary opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- line 2 : Section Business -->
    <div class="row">
        <!-- Graphic Principal -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">Évolution du Chiffre d'Affaires</h6>
                    <select name="period" id="salesChartPeriodSelector" class="form-select form-select-sm"
                        style="width: auto;">
                        <option value="7days" @if ($period == '7days') selected @endif>7 Derniers Jours</option>
                        <option value="1month" @if ($period == '1month') selected @endif>4 Dernières Semaines
                        </option>
                        <option value="1year" @if ($period == '1year') selected @endif>12 Derniers Mois</option>
                    </select>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="position: relative; height: 320px;">
                        <!-- Spinner loading -->
                        <div id="chartSpinner" class="d-none justify-content-center align-items-center"
                            style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.8); z-index: 10;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Résumé Performance (Side Panel) -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4 h-100">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">Business Performance</h6>
                </div>
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="mb-4">
                        <div class="small text-muted mb-1">Today Sales</div>
                        <div class="h4 mb-0 font-weight-bold text-dark">{{ number_format($salesToday, 2, ',', ' ') }} MGA
                        </div>
                        <div class="progress mt-2" style="height: 5px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <div class="small text-muted mb-1">this Month Sales</div>
                        <div class="h4 mb-0 font-weight-bold text-dark">{{ number_format($salesThisMonth, 2, ',', ' ') }} MGA
                        </div>
                        <div class="progress mt-2" style="height: 5px;">
                            <div class="progress-bar bg-info" role="progressbar" style="width: 100%"></div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <div class="small text-muted mb-1">Sale's Target</div>
                        <div class="h4 mb-0 font-weight-bold text-dark">{{ $totalSales }}</div>
                        <div class="progress mt-2" style="height: 5px;">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: 100%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        $(document).ready(function() {
            const ctx = document.getElementById('salesChart').getContext('2d');

            // Initialisation du graphique avec une configuration de base et des données vides
            const salesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Chiffre d\'affaires (MGA)',
                        data: [],
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78, 115, 223, 0.05)',
                        borderWidth: 2,
                        pointRadius: 3,
                        pointBackgroundColor: '#4e73df',
                        pointBorderColor: '#4e73df',
                        pointHoverRadius: 3,
                        pointHoverBackgroundColor: '#4e73df',
                        pointHoverBorderColor: '#4e73df',
                        pointHitRadius: 10,
                        pointBorderWidth: 2,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: "rgb(255,255,255)",
                            bodyColor: "#858796",
                            titleMarginBottom: 10,
                            titleColor: '#6e707e',
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + new Intl.NumberFormat(
                                        'fr-FR', {
                                            style: 'currency',
                                            currency: 'MGA'
                                        }).format(context.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                maxTicksLimit: 7
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                maxTicksLimit: 5,
                                padding: 10,
                                callback: function(value) {
                                    return value + ' MGA';
                                }
                            },
                            grid: {
                                color: "rgb(234, 236, 244)",
                                drawBorder: false,
                                borderDash: [2],
                            }
                        }
                    }
                }
            });

            // Fonction pour mettre à jour le graphique via un appel AJAX
            function updateSalesChart(period) {
                const spinner = $('#chartSpinner');

                // Afficher le spinner
                spinner.removeClass('d-none').addClass('d-flex');

                $.ajax({
                    url: "{{ route('admin.dashboard.chart-data') }}", // Nouvelle route API
                    type: 'GET',
                    data: {
                        period: period
                    },
                    success: function(response) {
                        salesChart.data.labels = response.labels;
                        salesChart.data.datasets[0].data = response.values;
                        salesChart.update();
                        spinner.addClass('d-none').removeClass('d-flex');
                    },
                    error: function(xhr) {
                        console.error("Erreur lors du chargement des données :", xhr.responseText);
                        alert("Impossible de charger les données du graphique.");
                        spinner.addClass('d-none').removeClass('d-flex');
                    }
                });
            }

            // Attacher l'événement 'change' au sélecteur de période
            $('#salesChartPeriodSelector').on('change', function() {
                updateSalesChart($(this).val());
            });

            // Chargement initial des données au chargement de la page
            const initialPeriod = $('#salesChartPeriodSelector').val();
            updateSalesChart(initialPeriod);
        });
    </script>
@endpush
