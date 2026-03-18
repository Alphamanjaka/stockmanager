@extends('layouts.app-front-office')

@section('title', 'Historique des Ventes')

@section('content')

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-success text-white shadow-sm">
                <div class="card-body">
                    <h6>Business Volume </h6>
                    <h3>{{ number_format($total_revenue, 2) }} Mga</h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-danger text-white shadow-sm">
                <div class="card-body">
                    <h6>Total Discounts </h6>
                    <h3>{{ number_format($total_discount, 2) }} Mga</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Recent Transactions</h5>
            <a href="{{ route('saler.create') }}" class="btn btn-primary btn-sm"> <i class="bi bi-plus-circle"></i> New Sale</a>
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
