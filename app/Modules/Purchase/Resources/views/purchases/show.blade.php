@extends('layouts.app-back-office')

@use('App\Modules\Purchase\Models\Purchase')

@section('title', __('Purchase :reference', ['reference' => $purchase->reference]))
@section('subtitle', $purchase->created_at->format('d/m/Y H:i'))

@php
    $draft = $purchase->state === Purchase::DRAFT;
    $received = $purchase->state === Purchase::RECEIVED;
    $remaining = $purchase->remainingQuantity();
    // Livraisons : mouvements d'entrée en stock de l'achat, regroupés par minute et par auteur
    $deliveries = $purchase->receptions->sortByDesc('id')
        ->groupBy(fn ($movement) => $movement->created_at->format('Y-m-d H:i') . '|' . $movement->user_id);
@endphp

@section('actions')
    <a href="{{ route('admin.purchases.pdf.preview', $purchase->id) }}" class="btn btn-outline-secondary" target="_blank">
        <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> PDF
    </a>
    {{-- Étiquettes des articles déjà livrés, une par article reçu --}}
    @php $deliveredItems = $purchase->items->filter(fn ($item) => $item->received_quantity > 0); @endphp
    @if ($deliveredItems->isNotEmpty())
        <a href="{{ route('admin.labels.create', ['variants' => $deliveredItems->mapWithKeys(fn ($item) => [$item->product_color_id => $item->received_quantity])->all()]) }}"
            class="btn btn-outline-secondary">
            <i class="bi bi-upc-scan" aria-hidden="true"></i> {{ __('Labels') }}
        </a>
    @endif
    @if ($draft)
        <a href="{{ route('admin.purchases.edit', $purchase->id) }}" class="btn btn-primary">
            <i class="bi bi-pencil" aria-hidden="true"></i> {{ __('Edit') }}
        </a>
    @elseif ($purchase->isAwaitingDelivery())
        <a href="{{ route('admin.purchases.receive', $purchase->id) }}" class="btn btn-primary">
            <i class="bi bi-truck" aria-hidden="true"></i> {{ __('Record a delivery') }}
        </a>
    @endif
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="ui-stack">
                <x-ui.card :title="__('Purchased items')" flush>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Product') }}</th>
                                    <th class="text-end">{{ $draft ? __('Quantity') : __('Ordered') }}</th>
                                    @unless ($draft)
                                        <th class="text-end">{{ __('Received') }}</th>
                                        <th class="text-end">{{ $received ? __('Not delivered') : __('To deliver') }}</th>
                                    @endunless
                                    <th class="text-end">{{ __('Unit cost') }}</th>
                                    <th class="text-end">{{ __('Subtotal') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($purchase->items as $item)
                                    <tr>
                                        <td>{{ $item->productColor->toString() }}</td>
                                        <td class="text-end">{{ $item->quantity }}</td>
                                        @unless ($draft)
                                            <td class="text-end">{{ $item->received_quantity }}</td>
                                            <td class="text-end">
                                                @if ($item->remainingQuantity() > 0)
                                                    <x-ui.badge :tone="$received ? 'neutral' : 'warning'">{{ $item->remainingQuantity() }}</x-ui.badge>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        @endunless
                                        <td class="text-end">{{ money($item->unit_price) }}</td>
                                        <td class="text-end">{{ money($item->subtotal) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <x-slot:footer>
                        <div class="ui-totals">
                            <div><span class="text-muted">{{ __('Gross total') }}</span><span>{{ money($purchase->total_amount) }}</span></div>
                            <div>
                                <span class="text-muted">{{ __('Discount') }}</span>
                                <span @class(['text-danger' => $purchase->discount > 0])>{{ $purchase->discount > 0 ? '-' : '' }}{{ money($purchase->discount) }}</span>
                            </div>
                            <div class="ui-totals-grand"><span>{{ __('Net total') }}</span><span>{{ money($purchase->total_net) }}</span></div>
                            @if ($purchase->state === Purchase::PARTIALLY_RECEIVED || ($received && $remaining > 0))
                                <div><span class="text-muted">{{ __('Received (net)') }}</span><span>{{ money($purchase->receivedValue()) }}</span></div>
                            @endif
                            @if ($purchase->state === Purchase::PARTIALLY_RECEIVED)
                                <div><span class="text-muted">{{ __('Still expected (net)') }}</span><span>{{ money($purchase->remainingValue()) }}</span></div>
                            @endif
                        </div>
                    </x-slot:footer>
                </x-ui.card>

                @if ($deliveries->isNotEmpty())
                    <x-ui.card :title="__('Deliveries')" :count="$deliveries->count()" flush>
                        <ul class="list-group list-group-flush">
                            @foreach ($deliveries as $movements)
                                <li class="list-group-item">
                                    <div class="d-flex flex-wrap justify-content-between gap-2">
                                        <span class="fw-semibold">
                                            {{ $movements->first()->created_at->format('d/m/Y H:i') }} · {{ $movements->first()->user->name ?? '—' }}
                                        </span>
                                        <span class="text-success text-nowrap">
                                            {{ __(':count item(s)', ['count' => $movements->sum('quantity')]) }}
                                        </span>
                                    </div>
                                    @foreach ($movements as $movement)
                                        <div class="ui-cell-meta d-flex justify-content-between gap-2">
                                            <span>{{ $movement->productColor?->label ?? '—' }}</span>
                                            <span class="text-nowrap">+{{ $movement->quantity }}</span>
                                        </div>
                                    @endforeach
                                </li>
                            @endforeach
                        </ul>
                    </x-ui.card>
                @endif
            </div>
        </div>

        <div class="col-lg-4">
            <div class="ui-stack">
                <x-ui.card :title="__('Status')">
                    <div class="mb-3"><x-ui.status :value="$purchase->state" /></div>
                    {{-- Réception définitive : le stock a déjà été ajouté --}}
                    @if ($received)
                        <p class="text-muted small mb-0">
                            {{ __('Stock has been updated. The status can no longer be changed.') }}
                            @if ($remaining > 0)
                                {{ __('The order was closed with :count item(s) not delivered.', ['count' => $remaining]) }}
                            @endif
                        </p>
                    @else
                        @if ($purchase->state === Purchase::PARTIALLY_RECEIVED)
                            <p class="text-muted small">
                                {{ __(':count item(s) still to be delivered. Record each delivery as it arrives.', ['count' => $remaining]) }}
                            </p>
                        @endif

                        {{-- Étape suivante : le stock n'augmente qu'à la réception, des quantités livrées --}}
                        <div class="d-grid gap-2">
                            @if ($draft)
                                <form action="{{ route('admin.purchases.updateState', $purchase->id) }}" method="POST" class="d-grid gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" name="state" value="Received" class="btn btn-primary"
                                        data-confirm="{{ __('Receive this purchase now?') }}" data-confirm-ok="{{ __('Receive') }}"
                                        data-confirm-text="{{ __('The quantities of every line are added to the stock. This cannot be undone.') }}">
                                        <i class="bi bi-box-arrow-in-down" aria-hidden="true"></i> {{ __('Receive now') }}
                                    </button>
                                    <a href="{{ route('admin.purchases.receive', $purchase->id) }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-truck" aria-hidden="true"></i> {{ __('Record a partial delivery') }}
                                    </a>
                                    <button type="submit" name="state" value="Ordered" class="btn btn-outline-secondary">
                                        <i class="bi bi-send" aria-hidden="true"></i> {{ __('Mark as ordered') }}
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('admin.purchases.updateState', $purchase->id) }}" method="POST" class="d-grid gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" name="state" value="Received" class="btn btn-outline-primary"
                                        data-confirm="{{ __('Receive everything still expected?') }}" data-confirm-ok="{{ __('Receive') }}"
                                        data-confirm-text="{{ __('The remaining quantity of every line is added to the stock. This cannot be undone.') }}">
                                        <i class="bi bi-box-arrow-in-down" aria-hidden="true"></i> {{ __('Receive everything still expected') }}
                                    </button>
                                    @if ($purchase->state === Purchase::ORDERED)
                                        <button type="submit" name="state" value="Draft" class="btn btn-outline-secondary">
                                            <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> {{ __('Back to draft') }}
                                        </button>
                                    @endif
                                </form>
                                @if ($purchase->state === Purchase::PARTIALLY_RECEIVED)
                                    {{-- Le fournisseur ne livrera pas le reste : il ne compte plus comme commandé --}}
                                    <form action="{{ route('admin.purchases.close', $purchase->id) }}" method="POST" class="d-grid">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger"
                                            data-confirm="{{ __('Close this order?') }}" data-confirm-tone="danger" data-confirm-ok="{{ __('Close the order') }}"
                                            data-confirm-text="{{ __('The remaining items will no longer be expected from the supplier. The items already received stay in stock.') }}">
                                            <i class="bi bi-x-circle" aria-hidden="true"></i> {{ __('Close the order (rest not delivered)') }}
                                        </button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    @endif
                </x-ui.card>

                <x-ui.card :title="__('Supplier')">
                    <dl class="ui-dl">
                        <dt>{{ __('Name') }}</dt>
                        <dd>
                            @if ($purchase->supplier)
                                <a href="{{ route('admin.suppliers.show', $purchase->supplier->id) }}">{{ $purchase->supplier->name }}</a>
                            @else
                                {{ __('documents.not_specified') }}
                            @endif
                        </dd>
                        <dt>{{ __('Email') }}</dt>
                        <dd>{{ $purchase->supplier->email ?? '—' }}</dd>
                        <dt>{{ __('Phone') }}</dt>
                        <dd>{{ $purchase->supplier->phone ?? '—' }}</dd>
                        <dt>{{ __('Their reference') }}</dt>
                        <dd>{{ $purchase->supplier_reference ?: '—' }}</dd>
                    </dl>
                </x-ui.card>

                @if ($purchase->notes)
                    <x-ui.card :title="__('Notes')">
                        <p class="text-prewrap mb-0">{{ $purchase->notes }}</p>
                    </x-ui.card>
                @endif
            </div>
        </div>
    </div>
@endsection
