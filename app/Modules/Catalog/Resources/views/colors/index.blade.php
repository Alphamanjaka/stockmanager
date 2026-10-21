@extends('layouts.app-back-office')

@section('title', __('Colors'))

@section('actions')
    <x-ui.actions-menu>
        <x-ui.export-items list="colors" />
        <li><hr class="dropdown-divider"></li>
        <x-ui.menu-item :href="route('admin.imports.index')" icon="bi-box-arrow-in-down">{{ __('Import colors') }}</x-ui.menu-item>
    </x-ui.actions-menu>
    <a href="{{ route('admin.colors.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> {{ __('New color') }}
    </a>
@endsection

@php $archived = request('status') === 'archived'; @endphp

@section('content')
    <div class="ui-stack">
        @if ($archived)
            <x-ui.archived-notice />
        @endif

        <x-ui.card flush :pagination="$items" data-bulk>
            <x-slot:toolbar>
                <x-ui.filters :action="route('admin.colors.index')">
                    <x-ui.search :placeholder="__('Search by name...')" />
                    <x-ui.status-filter />
                </x-ui.filters>
            </x-slot:toolbar>

            <x-ui.bulk-bar export="colors" :archive="route('admin.colors.archive')" :restore="route('admin.colors.restore')" />

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <x-ui.bulk-check-all />
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Code') }}</th>
                            <th class="text-end"><span class="visually-hidden">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <x-ui.bulk-check :value="$item->id" :label="$item->name" />
                                <td>
                                    <span class="d-inline-flex align-items-center gap-2 fw-semibold">
                                        {{-- Seules les couleurs CSS simples (#hex ou nom) sont reprises dans le style --}}
                                        <span class="ui-swatch" @if (preg_match('/^(#[0-9a-f]{3,8}|[a-z]+)$/i', (string) $item->code))
                                            style="background: {{ $item->code }}" @endif></span>
                                        {{ $item->name }}
                                    </span>
                                </td>
                                <td>
                                    @if ($item->code)
                                        <code>{{ $item->code }}</code>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <x-ui.actions-menu row>
                                        <x-ui.menu-item :href="route('admin.colors.edit', $item->id)" icon="bi-pencil">{{ __('Edit') }}</x-ui.menu-item>
                                        @if ($archived)
                                            <x-ui.menu-item :action="route('admin.colors.restore')" :ids="[$item->id]"
                                                icon="bi-arrow-counterclockwise">{{ __('Restore') }}</x-ui.menu-item>
                                            <li><hr class="dropdown-divider"></li>
                                            <x-ui.menu-item :action="route('admin.colors.destroy', $item->id)" method="DELETE"
                                                icon="bi-trash3" tone="danger" :confirm="__('Delete this color permanently?')"
                                                :confirm-text="__('Only possible if no variant uses it.')">{{ __('Delete permanently') }}</x-ui.menu-item>
                                        @else
                                            <x-ui.menu-item :action="route('admin.colors.archive')" :ids="[$item->id]" icon="bi-archive"
                                                :confirm="__('Archive this color?')">{{ __('Archive') }}</x-ui.menu-item>
                                        @endif
                                    </x-ui.actions-menu>
                                </td>
                            </tr>
                        @empty
                            <x-ui.empty-row :colspan="4" icon="bi-palette">
                                {{ $archived ? __('No archived color.') : __('No color found.') }}
                            </x-ui.empty-row>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
@endsection
