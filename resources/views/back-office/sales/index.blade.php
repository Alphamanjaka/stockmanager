@extends('layouts.app-back-office')

@section('title', 'Ventes')

@section('content')

    {{-- KPI Section --}}
    <div class="card shadow-sm mb-3 border-0 bg-light">
        <div class="card-body py-2">
            <div class="row align-items-center text-center text-md-start">
                <div class="col-md-6 border-end-md">
                    <div class="d-flex align-items-center justify-content-center justify-content-md-start px-2">
                        <i class="fas fa-chart-line text-success me-3"></i>
                        <div>
                            <span class="text-xs text-uppercase font-weight-bold text-muted d-block"
                                style="font-size: 0.65rem;">Business Volume</span>
                            <span class="h6 mb-0 font-weight-bold">
                                {{ number_format($total_revenue, 2) }} <small>MGA</small>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-center justify-content-md-start px-2">
                        <i class="fas fa-percent text-danger me-3"></i>
                        <div>
                            <span class="text-xs text-uppercase font-weight-bold text-muted d-block"
                                style="font-size: 0.65rem;">Total Discounts</span>
                            <span class="h6 mb-0 font-weight-bold text-danger">
                                {{ number_format($total_discount, 2) }} <small>MGA</small>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- boutton creer vente --}}
    <div class="row mb-4">
        <div class="col-md-12 text-end">
            <a href="{{ route('admin.sales.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Create Sale
            </a>
        </div>
    </div>



    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Reference</th>
                        <th>Date</th>
                        <th>Total Gross</th>
                        <th>Discount</th>
                        <th>Total Net</th>
                        {{-- saler --}}
                        <th class="text-center">Saler</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sales as $sale)
                        <tr>
                            <td class="fw-bold">{{ $sale->reference }}</td>
                            <td>{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ number_format($sale->total_brut, 2) }} Mga</td>
                            <td class="text-danger">-{{ number_format($sale->discount, 2) }} Mga</td>
                            <td class="fw-bold text-success">{{ number_format($sale->total_net, 2) }} Mga</td>
                            <td class="text-center">
                                {{ $sale->user->name ?? 'N/A' }}
                            </td>
                            <td class="text-center">

                                <a href="{{ route('admin.sales.show', $sale->id) }}" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-eye"></i> Details
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $sales->links() }}
        </div>
    </div>
@endsection
