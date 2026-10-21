@extends('layouts.app-front-office')

@section('title', __('My sales'))

@section('actions')
    <a href="{{ route('saler.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> {{ __('New sale') }}
    </a>
@endsection

@section('content')
    <div class="ui-stack">
        <div class="ui-stats">
            <x-ui.stat :label="__('Business volume')" :value="money($total_revenue)" icon="bi-wallet2" tone="success" />
            <x-ui.stat :label="__('Total discounts')" :value="money($total_discount)" icon="bi-percent" tone="danger" />
        </div>

        <x-ui.card :title="__('Recent transactions')" flush :pagination="$sales">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('Reference') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th class="text-end">{{ __('Gross total') }}</th>
                            <th class="text-end">{{ __('Discount') }}</th>
                            <th class="text-end">{{ __('Net total') }}</th>
                            <th class="text-end"><span class="visually-hidden">{{ __('Actions') }}</span></th>
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
                                    <a href="{{ route('saler.show', $sale->id) }}" class="btn btn-icon" title="{{ __('View') }}" aria-label="{{ __('View') }}">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <x-ui.empty-row :colspan="6" icon="bi-receipt">{{ __('No sale yet.') }}</x-ui.empty-row>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
@endsection
