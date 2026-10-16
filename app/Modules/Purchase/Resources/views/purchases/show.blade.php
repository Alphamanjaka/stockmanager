@extends('layouts.app-back-office')

@section('title', 'Purchase ' . $purchase->reference)
@section('subtitle', $purchase->created_at->format('d/m/Y H:i'))

@section('actions')
    <a href="{{ route('admin.purchases.pdf.preview', $purchase->id) }}" class="btn btn-outline-secondary" target="_blank">
        <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> PDF
    </a>
    @if ($purchase->state === 'Draft')
        <a href="{{ route('admin.purchases.edit', $purchase->id) }}" class="btn btn-primary">
            <i class="bi bi-pencil" aria-hidden="true"></i> Edit
        </a>
    @endif
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            <x-ui.card title="Purchased items" flush>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Unit cost</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($purchase->items as $item)
                                <tr>
                                    <td>{{ $item->productColor->toString() }}</td>
                                    <td class="text-end">{{ $item->quantity }}</td>
                                    <td class="text-end">{{ money($item->unit_price) }}</td>
                                    <td class="text-end">{{ money($item->subtotal) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-slot:footer>
                    <div class="ui-totals">
                        <div><span class="text-muted">Gross total</span><span>{{ money($purchase->total_amount) }}</span></div>
                        <div>
                            <span class="text-muted">Discount</span>
                            <span @class(['text-danger' => $purchase->discount > 0])>{{ $purchase->discount > 0 ? '-' : '' }}{{ money($purchase->discount) }}</span>
                        </div>
                        <div class="ui-totals-grand"><span>Net total</span><span>{{ money($purchase->total_net) }}</span></div>
                    </div>
                </x-slot:footer>
            </x-ui.card>
        </div>

        <div class="col-lg-4">
            <div class="ui-stack">
                <x-ui.card title="Status">
                    <div class="mb-3"><x-ui.status :value="$purchase->state" /></div>
                    {{-- Réception définitive : le stock a déjà été ajouté --}}
                    @if ($purchase->state === 'Received')
                        <p class="text-muted small mb-0">Stock has been updated. The status can no longer be changed.</p>
                    @else
                        <form action="{{ route('admin.purchases.updateState', $purchase->id) }}" method="POST" class="d-flex gap-2">
                            @csrf
                            @method('PATCH')
                            <select name="state" class="form-select" aria-label="New status">
                                <option value="Draft" @selected($purchase->state == 'Draft')>Draft</option>
                                <option value="Ordered" @selected($purchase->state == 'Ordered')>Ordered</option>
                                <option value="Received">Received</option>
                            </select>
                            <button type="submit" class="btn btn-outline-secondary">Update</button>
                        </form>
                    @endif
                </x-ui.card>

                <x-ui.card title="Supplier">
                    <dl class="ui-dl">
                        <dt>Name</dt>
                        <dd>
                            @if ($purchase->supplier)
                                <a href="{{ route('admin.suppliers.show', $purchase->supplier->id) }}">{{ $purchase->supplier->name }}</a>
                            @else
                                Non renseigné
                            @endif
                        </dd>
                        <dt>Email</dt>
                        <dd>{{ $purchase->supplier->email ?? '—' }}</dd>
                        <dt>Phone</dt>
                        <dd>{{ $purchase->supplier->phone ?? '—' }}</dd>
                    </dl>
                </x-ui.card>
            </div>
        </div>
    </div>
@endsection
