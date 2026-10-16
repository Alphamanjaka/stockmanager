@extends('layouts.app-back-office')

@section('title', 'Colors')

@section('actions')
    <a href="{{ route('admin.colors.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> New color
    </a>
@endsection

@section('content')
    <x-ui.card flush :pagination="$items">
        <x-slot:toolbar>
            {{-- Filtre local de la page affichée --}}
            <div class="ui-filters">
                <x-ui.search name="" id="color-filter" placeholder="Filter by name or code..." />
            </div>
        </x-slot:toolbar>

        <div class="table-responsive">
            <table class="table table-hover" id="colors-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr data-search="{{ strtolower($item->name . ' ' . ($item->code ?? '')) }}">
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
                                <div class="ui-row-actions">
                                    <a href="{{ route('admin.colors.edit', $item->id) }}" class="btn btn-icon" title="Edit" aria-label="Edit">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                    </a>
                                    <x-ui.delete-button :action="route('admin.colors.destroy', $item->id)" confirm="Delete this color?" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-row :colspan="3" icon="bi-palette">No color found.</x-ui.empty-row>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
@endsection

@push('scripts')
    <script>
        document.getElementById('color-filter').addEventListener('input', function () {
            const query = this.value.toLowerCase();
            document.querySelectorAll('#colors-table tbody tr[data-search]').forEach(function (row) {
                row.hidden = !row.dataset.search.includes(query);
            });
        });
    </script>
@endpush
