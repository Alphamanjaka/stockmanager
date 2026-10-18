@extends('layouts.app-back-office')

@section('title', 'Stock counts')

@section('actions')
    <x-ui.actions-menu>
        <x-ui.export-items list="inventories" />
    </x-ui.actions-menu>
    <a href="{{ route('admin.inventories.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> New stock count
    </a>
@endsection

@section('content')
    <x-ui.card flush :pagination="$inventories" data-bulk>
        <x-ui.bulk-bar export="inventories" />

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <x-ui.bulk-check-all />
                        <th>Reference</th>
                        <th>Scope</th>
                        <th>Status</th>
                        <th class="text-end">Counted lines</th>
                        <th>Opened</th>
                        <th>Validated</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inventories as $inventory)
                        <tr>
                            <x-ui.bulk-check :value="$inventory->id" :label="$inventory->reference" />
                            <td><a href="{{ route('admin.inventories.show', $inventory->id) }}" class="link-plain">{{ $inventory->reference }}</a></td>
                            <td>{{ $inventory->category->name ?? 'All products' }}</td>
                            <td>
                                @if ($inventory->isDraft())
                                    <x-ui.badge tone="warning" dot>Draft</x-ui.badge>
                                @else
                                    <x-ui.badge tone="success" dot>Validated</x-ui.badge>
                                @endif
                            </td>
                            <td class="text-end">{{ $inventory->counted_lines_count }} / {{ $inventory->lines_count }}</td>
                            <td>
                                {{ $inventory->created_at->format('d/m/Y H:i') }}
                                <div class="ui-cell-meta">{{ $inventory->user->name ?? '' }}</div>
                            </td>
                            <td>{{ $inventory->validated_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.inventories.show', $inventory->id) }}" class="btn btn-sm btn-outline-secondary">
                                    @if ($inventory->isDraft())
                                        <i class="bi bi-pencil-square" aria-hidden="true"></i> Count
                                    @else
                                        <i class="bi bi-file-earmark-text" aria-hidden="true"></i> Report
                                    @endif
                                </a>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-row :colspan="8" icon="bi-clipboard-check" title="No stock count yet">
                            A stock count compares the physical stock with the system stock and adjusts it.
                        </x-ui.empty-row>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
@endsection
