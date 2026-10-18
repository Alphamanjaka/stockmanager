@extends('layouts.app-back-office')

@section('title', $supplier->name)
@section('subtitle', 'Supplier since ' . $supplier->created_at->format('d/m/Y'))

@section('actions')
    <x-ui.actions-menu>
        @if ($supplier->isArchived())
            {{-- Possible seulement sans achat : sinon la base refuse et le fournisseur reste archivé --}}
            <x-ui.menu-item :action="route('admin.suppliers.destroy', $supplier->id)" method="DELETE" icon="bi-trash3" tone="danger"
                :disabled="$purchases->total() > 0" :title="$purchases->total() > 0 ? 'It has purchases: it stays archived' : null"
                confirm="Delete this supplier permanently?">Delete permanently</x-ui.menu-item>
        @else
            <x-ui.menu-item :action="route('admin.suppliers.archive')" :ids="[$supplier->id]" icon="bi-archive"
                confirm="Archive this supplier?"
                confirm-text="It will no longer be offered in purchase orders. Its purchases keep it.">Archive</x-ui.menu-item>
        @endif
    </x-ui.actions-menu>
    <a href="{{ route('admin.suppliers.edit', $supplier->id) }}" class="btn btn-primary">
        <i class="bi bi-pencil" aria-hidden="true"></i> Edit
    </a>
@endsection

@section('content')
    <div class="ui-stack">
        @if ($supplier->isArchived())
            <x-ui.archived-notice :restore="route('admin.suppliers.restore')" :id="$supplier->id" :date="$supplier->archived_at">
                This supplier is <strong>archived</strong>: it is no longer offered in purchase orders.
            </x-ui.archived-notice>
        @endif
        <div class="ui-stats">
            <x-ui.stat label="Business volume" :value="money($totalSpent)" icon="bi-cash-stack" tone="primary"
                :hint="'On ' . $purchases->total() . ' purchase(s)'" />
            <x-ui.stat label="Last purchase" :value="$lastPurchase?->created_at->format('d/m/Y') ?? '—'" icon="bi-calendar-check"
                tone="info" :hint="$lastPurchase ? $lastPurchase->reference : 'No purchase yet'" />
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <x-ui.card title="Contact information" class="h-100">
                    <dl class="ui-dl">
                        <dt>Phone</dt>
                        <dd>{{ $supplier->phone ?: '—' }}</dd>
                        <dt>Email</dt>
                        <dd>
                            @if ($supplier->email)
                                <a href="mailto:{{ $supplier->email }}">{{ $supplier->email }}</a>
                            @else
                                —
                            @endif
                        </dd>
                        <dt>Address</dt>
                        <dd>{{ $supplier->address ?: '—' }}</dd>
                        <dt>Notes</dt>
                        <dd class="text-prewrap">{{ $supplier->notes ?: '—' }}</dd>
                    </dl>
                </x-ui.card>
            </div>

            <div class="col-lg-8">
                <x-ui.card title="Top products purchased" flush class="h-100">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Total cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($topProducts as $product)
                                    <tr>
                                        <td>{{ $product->name }}</td>
                                        <td class="text-end">{{ $product->total_qty }}</td>
                                        <td class="text-end">{{ money($product->total_cost) }}</td>
                                    </tr>
                                @empty
                                    <x-ui.empty-row :colspan="3" icon="bi-trophy">No product purchased from this supplier yet.</x-ui.empty-row>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-ui.card>
            </div>
        </div>

        <x-ui.card title="Purchase history" flush :pagination="$purchases">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="text-end">Total net</th>
                            <th class="text-end"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($purchases as $purchase)
                            <tr>
                                <td><a href="{{ route('admin.purchases.show', $purchase->id) }}" class="link-plain">{{ $purchase->reference }}</a></td>
                                <td>{{ $purchase->created_at->format('d/m/Y H:i') }}</td>
                                <td><x-ui.status :value="$purchase->state" /></td>
                                <td class="text-end fw-semibold">{{ money($purchase->total_net) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.purchases.show', $purchase->id) }}" class="btn btn-icon" title="View" aria-label="View">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <x-ui.empty-row :colspan="5" icon="bi-receipt">No purchase recorded for this supplier.</x-ui.empty-row>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
@endsection
