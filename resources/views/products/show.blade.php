@extends('layouts.app-back-office')
@section('title', 'Detail of Product : ' . $product->name)

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Back to list
            </a>
            <div>
                <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-primary">
                    <i class="bi bi-pencil-square"></i> Edit
                </a>
            </div>
        </div>

        <div class="row">
            {{-- Product Details Card --}}
            <div class="col-lg-6">
                <div class="card shadow-sm mb-4 h-100">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-box"></i> Product Information</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Name:</strong>
                                <span>{{ $product->name }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Category:</strong>
                                <span>{{ $product->category->name ?? 'Not defined' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Selling Price:</strong>
                                <span class="fw-bold text-success">{{ number_format($product->price, 2) }} €</span>
                            </li>
                            <li class="list-group-item">
                                <strong>Description:</strong>
                                <p class="mt-2 text-muted">{{ $product->description ?? 'No description available' }}</p>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Stock Details Card --}}
            <div class="col-lg-6">
                <div class="card shadow-sm mb-4 h-100">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-warehouse"></i> Stock Status</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <strong>Current Stock:</strong>
                                <span
                                    class="badge fs-6 {{ $product->quantity_stock <= $product->alert_stock ? 'bg-danger' : 'bg-success' }}">
                                    {{ $product->quantity_stock }} units
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Alert Threshold:</strong>
                                <span>{{ $product->alert_stock }} units</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Created on:</strong>
                                <span>{{ $product->created_at->format('d/m/Y H:i') }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Last Updated:</strong>
                                <span>{{ $product->updated_at->format('d/m/Y H:i') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stock Evolution Chart --}}
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Stock Evolution</h5>
            </div>
            <div class="card-body" style="position: relative; height: 300px; width: 100%;">
                <canvas id="stockChart"></canvas>
            </div>
        </div>

        {{-- Stock Movements History --}}
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-arrows-alt"></i> Stock Movements History</h5>
            </div>
            <div class="card-body">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stockMovements as $movement)
                            <tr>
                                <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                                <td>{!! $movement->type === 'in'
                                    ? '<span class="badge bg-success">Entry</span>'
                                    : '<span class="badge bg-danger">Exit</span>' !!}</td>
                                <td class="fw-bold {{ $movement->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $movement->quantity }}</td>
                                <td>{{ $movement->reason }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">No stock movements recorded for this product.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-3 d-flex justify-content-center">{{ $stockMovements->links() }}</div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script type="module">
    $(function() {
        const ctx = document.getElementById('stockChart').getContext('2d');
        const alertThreshold = {!! $product->alert_stock !!}; // On pourrait passer cette valeur depuis le PHP ($product->min_stock)

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! $chartLabels !!},
                datasets: [{
                    label: 'Niveau du stock',
                    data: {!! $chartData !!},
                    borderColor: '#17a2b8',
                    backgroundColor: (context) => {
                        const chart = context.chart;
                        const {ctx, chartArea} = chart;
                        if (!chartArea) return null;

                        // Dégradé qui change de couleur si on passe sous le seuil
                        const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                        gradient.addColorStop(0, 'rgba(220, 53, 69, 0.2)'); // Rouge en bas
                        gradient.addColorStop(alertThreshold / 100, 'rgba(23, 162, 184, 0.2)'); // Transition
                        return gradient;
                    },
                    borderWidth: 3,
                    stepped: true,
                    fill: true,
                    pointRadius: (context) => context.raw === 0 ? 6 : 3, // Point plus gros si stock à zéro
                    pointBackgroundColor: (context) => context.raw < alertThreshold ? '#dc3545' : '#17a2b8'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    annotation: {
                        annotations: {
                            line1: {
                                type: 'line',
                                yMin: alertThreshold,
                                yMax: alertThreshold,
                                borderColor: 'rgba(220, 53, 69, 0.8)',
                                borderWidth: 2,
                                borderDash: [6, 6],
                                label: {
                                    display: true,
                                    content: 'Seuil d\'alerte',
                                    position: 'end'
                                }
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => ` Quantité : ${context.parsed.y} unités`,
                            footer: (context) => {
                                if (context[0].parsed.y < alertThreshold) return '⚠️ Stock critique !';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        type: 'time',
                        time: { unit: 'day' },
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: alertThreshold + 20 // Pour toujours voir le seuil
                    }
                }
            }
        });
    });
</script>
@endpush
