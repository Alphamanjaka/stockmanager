@extends('layouts.app-front-office')

@section('title', 'Historique des Ventes')

@section('content')

    {{-- KPI Section Compact --}}
    <div class="card shadow-sm mb-3 border-0 bg-light">
        <div class="card-body py-2">
            <div class="row align-items-center text-center text-md-start">
                <div class="col-md-6 border-end-md">
                    <div class="d-flex align-items-center justify-content-center justify-content-md-start px-2">
                        <i class="fas fa-wallet text-success me-3"></i>
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
                        <i class="fas fa-tag text-danger me-3"></i>
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

    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Recent Transactions</h5>
            <a href="{{ route('saler.create') }}" class="btn btn-primary btn-sm"> <i class="bi bi-plus-circle"></i> New
                Sale</a>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Reference</th>
                        <th>Date</th>
                        <th>Total Gross</th>
                        <th>Discount</th>
                        <th>Total Net</th>
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

                                <a href="{{ route('saler.show', $sale->id) }}" class="btn btn-sm btn-outline-info">
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
