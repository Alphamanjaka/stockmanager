@extends('layouts.app-back-office')

@section('title', $supplier->name)
@section('subtitle', 'Supplier since ' . $supplier->created_at->format('d/m/Y'))

@section('actions')
    <a href="{{ route('admin.suppliers.edit', $supplier->id) }}" class="btn btn-primary">
        <i class="bi bi-pencil" aria-hidden="true"></i> Edit
    </a>
@endsection

@section('content')
    <div class="ui-stack">
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

        {{-- Suppression volontairement difficile : il faut retaper le nom exact du fournisseur --}}
        <x-ui.card title="Danger zone" tone="danger">
            <p class="mb-3">
                Deleting this supplier is permanent.
                @if ($purchases->total() > 0)
                    Its <strong>{{ $purchases->total() }} purchase(s)</strong> will be kept, but without supplier.
                @endif
            </p>
            <form action="{{ route('admin.suppliers.destroy', $supplier->id) }}" method="POST" class="row g-2 align-items-end"
                id="delete-supplier-form">
                @csrf
                @method('DELETE')
                <div class="col-md-6">
                    <label for="confirm_name" class="form-label">
                        Type <strong>{{ $supplier->name }}</strong> to confirm
                    </label>
                    <input type="text" id="confirm_name" name="confirm_name" class="form-control" autocomplete="off"
                        data-expected="{{ $supplier->name }}">
                </div>
                <div class="col-md-auto">
                    <button type="submit" id="delete-supplier-button" class="btn btn-danger" disabled>
                        <i class="bi bi-trash3" aria-hidden="true"></i> Delete this supplier
                    </button>
                </div>
            </form>
        </x-ui.card>
    </div>

    @push('scripts')
        <script>
            // Le bouton ne s'active que si le nom tapé correspond exactement (vérifié aussi côté serveur)
            document.getElementById('confirm_name').addEventListener('input', function () {
                document.getElementById('delete-supplier-button').disabled = this.value.trim() !== this.dataset.expected;
            });
        </script>
    @endpush
@endsection
