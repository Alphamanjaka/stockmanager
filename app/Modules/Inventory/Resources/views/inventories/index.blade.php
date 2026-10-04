@extends('layouts.app-back-office')

@section('title', 'Stock Counts')
@section('actions')
    <a href="{{ route('admin.inventories.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> New Stock Count
    </a>
@endsection

@section('content')
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Reference</th>
                        <th>Scope</th>
                        <th>Status</th>
                        <th class="text-center">Counted lines</th>
                        <th>Opened</th>
                        <th>Validated</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inventories as $inventory)
                        <tr>
                            <td class="fw-bold">{{ $inventory->reference }}</td>
                            <td>{{ $inventory->category->name ?? 'All products' }}</td>
                            <td>
                                @if ($inventory->isDraft())
                                    <span class="badge bg-warning text-dark">Draft</span>
                                @else
                                    <span class="badge bg-success">Validated</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $inventory->counted_lines_count }} / {{ $inventory->lines_count }}</td>
                            <td>{{ $inventory->created_at->format('d/m/Y H:i') }} <span class="text-muted small">{{ $inventory->user->name ?? '' }}</span></td>
                            <td>{{ $inventory->validated_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.inventories.show', $inventory->id) }}" class="btn btn-sm btn-outline-primary">
                                    {{ $inventory->isDraft() ? 'Count' : 'Report' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                No stock count yet. A stock count compares the physical stock with the system stock
                                and adjusts it.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4 d-flex justify-content-center">
        {{ $inventories->links() }}
    </div>
@endsection
