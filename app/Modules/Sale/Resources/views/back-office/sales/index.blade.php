@extends('layouts.app-back-office')

@section('title', 'Sales')

@section('actions')
    <x-ui.actions-menu>
        <x-ui.export-items list="sales" />
    </x-ui.actions-menu>
    <a href="{{ route('admin.sales.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> New sale
    </a>
@endsection

@section('content')
    <div class="ui-stack">
        <div class="ui-stats">
            <x-ui.stat label="Business volume" :value="money($total_revenue)" icon="bi-graph-up-arrow" tone="success"
                :hint="$total_refunds > 0 ? money($total_refunds) . ' refunded (credit notes deducted)' : null" />
            <x-ui.stat label="Total discounts" :value="money($total_discount)" icon="bi-percent" tone="danger" />
            <x-ui.stat label="Gross margin" :value="money($margin['margin'])" icon="bi-cash-coin" tone="primary"
                :hint="($margin['rate'] === null ? 'No sale with a known cost' : $margin['rate'] . ' % of sales')
                    . ($margin['excluded'] > 0 ? ' · ' . $margin['excluded'] . ' sale(s) without a known cost not counted' : '')" />
        </div>

        @if ($customer)
            <x-ui.alert tone="info" icon="bi-funnel">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span>Sales of customer <strong>{{ $customer->name }}</strong></span>
                    <a href="{{ route('admin.sales.index') }}" class="btn btn-sm btn-outline-secondary">Show all sales</a>
                </div>
            </x-ui.alert>
        @endif

        <x-ui.card flush :pagination="$sales" data-bulk>
            <x-slot:toolbar>
                <x-ui.filters :action="route('admin.sales.index')">
                    @if ($customer)
                        <input type="hidden" name="customer" value="{{ $customer->id }}">
                    @endif
                    <x-ui.search placeholder="Reference or customer..." />
                    <div class="input-group ui-filter">
                        <span class="input-group-text">From</span>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" aria-label="From">
                        <span class="input-group-text">To</span>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" aria-label="To">
                    </div>
                </x-ui.filters>
            </x-slot:toolbar>

            <x-ui.bulk-bar export="sales" />

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <x-ui.bulk-check-all />
                            <th>Reference</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Seller</th>
                            <th class="text-end">Gross total</th>
                            <th class="text-end">Discount</th>
                            <th class="text-end">Net total</th>
                            <th class="text-end">Margin</th>
                            <th class="text-end"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sales as $sale)
                            <tr>
                                <x-ui.bulk-check :value="$sale->id" :label="$sale->reference" />
                                <td>
                                    <a href="{{ route('admin.sales.show', $sale->id) }}" class="link-plain">{{ $sale->reference }}</a>
                                    @if ($sale->returnStatus())
                                        <div><x-ui.badge :tone="$sale->returnStatus() === 'returned' ? 'danger' : 'warning'">
                                            {{ $sale->returnStatus() === 'returned' ? 'Cancelled' : 'Partially returned' }}
                                        </x-ui.badge></div>
                                    @endif
                                </td>
                                <td class="text-nowrap">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $sale->customer->name ?? '—' }}</td>
                                <td>{{ $sale->user->name ?? '—' }}</td>
                                <td class="text-end">{{ money($sale->total_brut) }}</td>
                                <td class="text-end text-danger">{{ $sale->discount > 0 ? '-' . money($sale->discount) : '—' }}</td>
                                <td class="text-end fw-semibold">
                                    {{ money($sale->total_net) }}
                                    @if ($sale->refundedTotal() > 0)
                                        <div class="ui-cell-meta text-danger">-{{ money($sale->refundedTotal()) }} refunded</div>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    @if ($sale->grossMargin() === null)
                                        <span class="text-muted" title="The cost of an item is not known">—</span>
                                    @else
                                        <span @class(['text-danger' => $sale->grossMargin() < 0])>{{ money($sale->grossMargin()) }}</span>
                                        <div class="ui-cell-meta">{{ $sale->marginRate() ?? 0 }} %</div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <x-ui.actions-menu row>
                                        <x-ui.menu-item :href="route('admin.sales.show', $sale->id)" icon="bi-eye">View</x-ui.menu-item>
                                        <x-ui.menu-item :href="route('admin.sales.pdf', $sale->id)" icon="bi-file-earmark-pdf">Invoice (PDF)</x-ui.menu-item>
                                        <x-ui.menu-item :href="route('admin.sales.receipt', $sale->id)" icon="bi-printer" target="_blank">Receipt</x-ui.menu-item>
                                    </x-ui.actions-menu>
                                </td>
                            </tr>
                        @empty
                            <x-ui.empty-row :colspan="10" icon="bi-receipt">No sale found.</x-ui.empty-row>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
@endsection
