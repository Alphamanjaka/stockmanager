@extends('layouts.app-back-office')

@section('title', 'Brands')

@section('actions')
    <a href="{{ route('admin.brands.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> New brand
    </a>
@endsection

@section('content')
    <x-ui.card flush :pagination="$brands">
        <x-slot:toolbar>
            <x-ui.filters :action="route('admin.brands.index')">
                <x-ui.search placeholder="Search brand..." />
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
                                <div class="ui-row-actions">
                                    <a href="{{ route('admin.brands.edit', $brand->id) }}" class="btn btn-icon" title="Edit" aria-label="Edit">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                    </a>
                                    {{-- Une marque utilisée par des variantes ne peut pas être supprimée --}}
                                    <x-ui.delete-button :action="route('admin.brands.destroy', $brand->id)" confirm="Delete this brand?"
                                        :disabled="$brand->variants_count > 0"
                                        :title="$brand->variants_count > 0 ? 'Used by variants: cannot be deleted' : 'Delete'" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-row :colspan="3" icon="bi-tag">No brand yet.</x-ui.empty-row>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
@endsection
