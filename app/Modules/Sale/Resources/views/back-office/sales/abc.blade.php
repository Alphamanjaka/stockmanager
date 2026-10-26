{{--
    Analyse ABC (AbcAnalysisService) : variantes classées par chiffre d'affaires sur la période.
    A : premiers 80 % du chiffre d'affaires, B : 15 % suivants, C : le reste (dont les variantes sans vente).
--}}
@extends('layouts.app-back-office')

@section('title', __('ABC analysis'))

@php
    $tones = ['A' => 'success', 'B' => 'warning', 'C' => 'neutral'];
    $hints = [
        'A' => __('First 80 % of revenue: never let them run out'),
        'B' => __('Next 15 % of revenue'),
        'C' => __('Last 5 % of revenue and products without sales: stock to reduce'),
    ];
@endphp

@section('actions')
    <x-ui.actions-menu>
        <x-ui.export-items list="abc-analysis" />
    </x-ui.actions-menu>
@endsection

@section('content')
    <div class="ui-stack">
        <div class="ui-stats">
            @foreach ($analysis['classes'] as $name => $group)
                <x-ui.stat :label="__('Class :class', ['class' => $name])" :tone="$tones[$name]"
                    :value="__(':count product(s)', ['count' => $group['count']])"
                    :href="route('admin.sales.abc', ['days' => $analysis['days'], 'class' => $name])"
                    :hint="__(':share % of revenue · :value in stock at cost', ['share' => $group['share'], 'value' => money($group['stock_value'])])" />
            @endforeach
        </div>

        <x-ui.card flush :pagination="$lines"
            :subtitle="__(':revenue of revenue since :date, credit notes deducted', ['revenue' => money($analysis['revenue']), 'date' => $analysis['from']->format('d/m/Y')])">
            <x-slot:toolbar>
                <x-ui.filters :action="route('admin.sales.abc')">
                    <select name="days" class="form-select ui-filter" aria-label="{{ __('Period') }}">
                        @foreach (\App\Modules\Sale\Services\AbcAnalysisService::PERIODS as $days)
                            <option value="{{ $days }}" @selected($analysis['days'] === $days)>{{ __('Last :days days', ['days' => $days]) }}</option>
                        @endforeach
                    </select>
                    <select name="class" class="form-select ui-filter" aria-label="{{ __('Class') }}">
                        <option value="">{{ __('All classes') }}</option>
                        @foreach (\App\Modules\Sale\Services\AbcAnalysisService::CLASSES as $name)
                            <option value="{{ $name }}" @selected($class === $name)>{{ __('Class :class', ['class' => $name]) }}</option>
                        @endforeach
                    </select>
                </x-ui.filters>
            </x-slot:toolbar>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('Class') }}</th>
                            <th>{{ __('Product') }}</th>
                            <th class="text-end">{{ __('Quantity sold') }}</th>
                            <th class="text-end">{{ __('Revenue') }}</th>
                            <th class="text-end">{{ __('Share') }}</th>
                            <th class="text-end">{{ __('Cumulative share') }}</th>
                            <th class="text-end">{{ __('Stock') }}</th>
                            <th class="text-end">{{ __('Stock value (cost)') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lines as $line)
                            <tr>
                                <td><x-ui.badge :tone="$tones[$line->class]" :title="$hints[$line->class]">{{ $line->class }}</x-ui.badge></td>
                                <td>
                                    <a href="{{ route('admin.products.show', $line->variant->product_id) }}" class="link-plain">{{ $line->variant->label }}</a>
                                    @if ($line->variant->sku)
                                        <div class="ui-cell-meta">{{ $line->variant->sku }}</div>
                                    @endif
                                </td>
                                <td class="text-end">{{ $line->quantity ?: '—' }}</td>
                                <td class="text-end fw-semibold">{{ $line->revenue > 0 ? money($line->revenue) : '—' }}</td>
                                <td class="text-end">{{ $line->share > 0 ? $line->share . ' %' : '—' }}</td>
                                <td class="text-end">{{ $line->revenue > 0 ? $line->cumulative . ' %' : '—' }}</td>
                                <td class="text-end">{{ $line->variant->stock }}</td>
                                <td class="text-end">{{ money($line->stock_value) }}</td>
                            </tr>
                        @empty
                            <x-ui.empty-row :colspan="8" icon="bi-bar-chart">{{ __('No product to analyse.') }}</x-ui.empty-row>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
@endsection
