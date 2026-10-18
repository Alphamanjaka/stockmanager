@extends('layouts.app-back-office')

@section('title', 'Colors')

@section('actions')
    <x-ui.actions-menu>
        <x-ui.export-items list="colors" />
        <li><hr class="dropdown-divider"></li>
        <x-ui.menu-item :href="route('admin.imports.index')" icon="bi-box-arrow-in-down">Import colors</x-ui.menu-item>
    </x-ui.actions-menu>
    <a href="{{ route('admin.colors.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> New color
    </a>
@endsection

@php $archived = request('status') === 'archived'; @endphp

@section('content')
    <div class="ui-stack">
        @if ($archived)
            <x-ui.archived-notice />
        @endif

        <x-ui.card flush :pagination="$items">
            <x-slot:toolbar>
                <x-ui.filters :action="route('admin.colors.index')">
                    <x-ui.search placeholder="Search by name..." />
                    <x-ui.status-filter />
                </x-ui.filters>
            </x-slot:toolbar>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th class="text-end"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
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
                                        <x-ui.menu-item :href="route('admin.colors.edit', $item->id)" icon="bi-pencil">Edit</x-ui.menu-item>
                                        @if ($archived)
                                            <x-ui.menu-item :action="route('admin.colors.restore')" :ids="[$item->id]"
                                                icon="bi-arrow-counterclockwise">Restore</x-ui.menu-item>
                                            <li><hr class="dropdown-divider"></li>
                                            <x-ui.menu-item :action="route('admin.colors.destroy', $item->id)" method="DELETE"
                                                icon="bi-trash3" tone="danger" confirm="Delete this color permanently?"
                                                confirm-text="Only possible if no variant uses it.">Delete permanently</x-ui.menu-item>
                                        @else
                                            <x-ui.menu-item :action="route('admin.colors.archive')" :ids="[$item->id]" icon="bi-archive"
                                                confirm="Archive this color?">Archive</x-ui.menu-item>
                                        @endif
                                    </x-ui.actions-menu>
                                </td>
                            </tr>
                        @empty
                            <x-ui.empty-row :colspan="3" icon="bi-palette">
                                {{ $archived ? 'No archived color.' : 'No color found.' }}
                            </x-ui.empty-row>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
@endsection
