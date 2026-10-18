@extends('layouts.app-back-office')

@section('title', 'Brands')

@section('actions')
    <x-ui.actions-menu>
        <x-ui.export-items list="brands" />
    </x-ui.actions-menu>
    <a href="{{ route('admin.brands.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> New brand
    </a>
@endsection

@php $archived = request('status') === 'archived'; @endphp

@section('content')
    <div class="ui-stack">
        @if ($archived)
            <x-ui.archived-notice />
        @endif

        <x-ui.card flush :pagination="$brands">
            <x-slot:toolbar>
                <x-ui.filters :action="route('admin.brands.index')">
                    <x-ui.search placeholder="Search brand..." />
                    <x-ui.status-filter />
                </x-ui.filters>
            </x-slot:toolbar>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th class="text-end">Variants</th>
                            <th class="text-end"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($brands as $brand)
                            <tr>
                                <td class="fw-semibold">{{ $brand->name }}</td>
                                <td class="text-end">{{ $brand->variants_count }}</td>
                                <td class="text-end">
                                    <x-ui.actions-menu row>
                                        <x-ui.menu-item :href="route('admin.brands.edit', $brand->id)" icon="bi-pencil">Edit</x-ui.menu-item>
                                        @if ($archived)
                                            <x-ui.menu-item :action="route('admin.brands.restore')" :ids="[$brand->id]"
                                                icon="bi-arrow-counterclockwise">Restore</x-ui.menu-item>
                                            {{-- Une marque portée par des variantes ne peut pas être supprimée --}}
                                            <li><hr class="dropdown-divider"></li>
                                            <x-ui.menu-item :action="route('admin.brands.destroy', $brand->id)" method="DELETE"
                                                icon="bi-trash3" tone="danger" :disabled="$brand->variants_count > 0"
                                                :title="$brand->variants_count > 0 ? 'Used by variants: it stays archived' : null"
                                                confirm="Delete this brand permanently?">Delete permanently</x-ui.menu-item>
                                        @else
                                            <x-ui.menu-item :action="route('admin.brands.archive')" :ids="[$brand->id]" icon="bi-archive"
                                                confirm="Archive this brand?"
                                                confirm-text="It will no longer be offered for new variants. Existing variants keep it.">Archive</x-ui.menu-item>
                                        @endif
                                    </x-ui.actions-menu>
                                </td>
                            </tr>
                        @empty
                            <x-ui.empty-row :colspan="3" icon="bi-tag">
                                {{ $archived ? 'No archived brand.' : 'No brand yet.' }}
                            </x-ui.empty-row>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
@endsection
