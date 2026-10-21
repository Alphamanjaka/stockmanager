@extends('layouts.app-back-office')

@section('title', __('Brands'))

@section('actions')
    <x-ui.actions-menu>
        <x-ui.export-items list="brands" />
    </x-ui.actions-menu>
    <a href="{{ route('admin.brands.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> {{ __('New brand') }}
    </a>
@endsection

@php $archived = request('status') === 'archived'; @endphp

@section('content')
    <div class="ui-stack">
        @if ($archived)
            <x-ui.archived-notice />
        @endif

        <x-ui.card flush :pagination="$brands" data-bulk>
            <x-slot:toolbar>
                <x-ui.filters :action="route('admin.brands.index')">
                    <x-ui.search :placeholder="__('Search brand...')" />
                    <x-ui.status-filter />
                </x-ui.filters>
            </x-slot:toolbar>

            <x-ui.bulk-bar export="brands" :archive="route('admin.brands.archive')" :restore="route('admin.brands.restore')" />

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <x-ui.bulk-check-all />
                            <th>{{ __('Name') }}</th>
                            <th class="text-end">{{ __('Variants') }}</th>
                            <th class="text-end"><span class="visually-hidden">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($brands as $brand)
                            <tr>
                                <x-ui.bulk-check :value="$brand->id" :label="$brand->name" />
                                <td class="fw-semibold">{{ $brand->name }}</td>
                                <td class="text-end">{{ $brand->variants_count }}</td>
                                <td class="text-end">
                                    <x-ui.actions-menu row>
                                        <x-ui.menu-item :href="route('admin.brands.edit', $brand->id)" icon="bi-pencil">{{ __('Edit') }}</x-ui.menu-item>
                                        @if ($archived)
                                            <x-ui.menu-item :action="route('admin.brands.restore')" :ids="[$brand->id]"
                                                icon="bi-arrow-counterclockwise">{{ __('Restore') }}</x-ui.menu-item>
                                            {{-- Une marque portée par des variantes ne peut pas être supprimée --}}
                                            <li><hr class="dropdown-divider"></li>
                                            <x-ui.menu-item :action="route('admin.brands.destroy', $brand->id)" method="DELETE"
                                                icon="bi-trash3" tone="danger" :disabled="$brand->variants_count > 0"
                                                :title="$brand->variants_count > 0 ? __('Used by variants: it stays archived') : null"
                                                :confirm="__('Delete this brand permanently?')">{{ __('Delete permanently') }}</x-ui.menu-item>
                                        @else
                                            <x-ui.menu-item :action="route('admin.brands.archive')" :ids="[$brand->id]" icon="bi-archive"
                                                :confirm="__('Archive this brand?')"
                                                :confirm-text="__('It will no longer be offered for new variants. Existing variants keep it.')">{{ __('Archive') }}</x-ui.menu-item>
                                        @endif
                                    </x-ui.actions-menu>
                                </td>
                            </tr>
                        @empty
                            <x-ui.empty-row :colspan="4" icon="bi-tag">
                                {{ $archived ? __('No archived brand.') : __('No brand yet.') }}
                            </x-ui.empty-row>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
@endsection
