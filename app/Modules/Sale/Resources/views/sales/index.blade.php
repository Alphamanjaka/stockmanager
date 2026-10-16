@extends('layouts.app-front-office')

@section('title', 'My sales')

@section('actions')
    <a href="{{ route('saler.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> New sale
    </a>
@endsection

@section('content')
    <div class="ui-stack">
        <div class="ui-stats">
            <x-ui.stat label="Business volume" :value="money($total_revenue)" icon="bi-wallet2" tone="success" />
            <x-ui.stat label="Total discounts" :value="money($total_discount)" icon="bi-percent" tone="danger" />
        </div>

        <x-ui.card title="Recent transactions" flush :pagination="$sales">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Date</th>
                            <th class="text-end">Gross total</th>
                            <th class="text-end">Discount</th>
                            <th class="text-end">Net total</th>
                            <th class="text-end"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sales as $sale)
                            <tr>
                                <td><a href="{{ route('saler.show', $sale->id) }}" class="link-plain">{{ $sale->reference }}</a></td>
                                <td class="text-nowrap">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-end">{{ money($sale->total_brut) }}</td>
                                <td class="text-end text-danger">{{ $sale->discount > 0 ? '-' . money($sale->discount) : '—' }}</td>
                                <td class="text-end fw-semibold">{{ money($sale->total_net) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('saler.show', $sale->id) }}" class="btn btn-icon" title="View" aria-label="View">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <x-ui.empty-row :colspan="6" icon="bi-receipt">No sale yet.</x-ui.empty-row>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
@endsection
