@extends('layouts.app-back-office')

@section('title', __('Purchase :reference', ['reference' => $purchase->reference]))
@section('subtitle', $purchase->created_at->format('d/m/Y H:i'))

@section('actions')
    <a href="{{ route('admin.purchases.pdf.preview', $purchase->id) }}" class="btn btn-outline-secondary" target="_blank">
        <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> PDF
    </a>
    @if ($purchase->state === 'Draft')
        <a href="{{ route('admin.purchases.edit', $purchase->id) }}" class="btn btn-primary">
            <i class="bi bi-pencil" aria-hidden="true"></i> {{ __('Edit') }}
        </a>
    @endif
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            <x-ui.card :title="__('Purchased items')" flush>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{{ __('Product') }}</th>
                                <th class="text-end">{{ __('Quantity') }}</th>
                                <th class="text-end">{{ __('Unit cost') }}</th>
                                <th class="text-end">{{ __('Subtotal') }}</th>
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
                        <div><span class="text-muted">{{ __('Gross total') }}</span><span>{{ money($purchase->total_amount) }}</span></div>
                        <div>
                            <span class="text-muted">{{ __('Discount') }}</span>
                            <span @class(['text-danger' => $purchase->discount > 0])>{{ $purchase->discount > 0 ? '-' : '' }}{{ money($purchase->discount) }}</span>
                        </div>
                        <div class="ui-totals-grand"><span>{{ __('Net total') }}</span><span>{{ money($purchase->total_net) }}</span></div>
                    </div>
                </x-slot:footer>
            </x-ui.card>
        </div>

        <div class="col-lg-4">
            <div class="ui-stack">
                <x-ui.card :title="__('Status')">
                    <div class="mb-3"><x-ui.status :value="$purchase->state" /></div>
                    {{-- Réception définitive : le stock a déjà été ajouté --}}
                    @if ($purchase->state === 'Received')
                        <p class="text-muted small mb-0">{{ __('Stock has been updated. The status can no longer be changed.') }}</p>
                    @else
                        {{-- Étape suivante : le stock n'augmente qu'à la réception --}}
                        <form action="{{ route('admin.purchases.updateState', $purchase->id) }}" method="POST" class="d-grid gap-2">
                            @csrf
                            @method('PATCH')
                            <button type="submit" name="state" value="Received" class="btn btn-primary"
                                data-confirm="{{ __('Receive this purchase now?') }}" data-confirm-ok="{{ __('Receive') }}"
                                data-confirm-text="{{ __('The quantities of every line are added to the stock. This cannot be undone.') }}">
                                <i class="bi bi-box-arrow-in-down" aria-hidden="true"></i> {{ __('Receive now') }}
                            </button>
                            @if ($purchase->state === 'Draft')
                                <button type="submit" name="state" value="Ordered" class="btn btn-outline-secondary">
                                    <i class="bi bi-send" aria-hidden="true"></i> {{ __('Mark as ordered') }}
                                </button>
                            @else
                                <button type="submit" name="state" value="Draft" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> {{ __('Back to draft') }}
                                </button>
                            @endif
                        </form>
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
