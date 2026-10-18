@extends('layouts.app-back-office')

@section('title', 'Categories')

@section('actions')
    <x-ui.actions-menu>
        <x-ui.export-items list="categories" />
        <li><hr class="dropdown-divider"></li>
        <x-ui.menu-item :href="route('admin.imports.index')" icon="bi-box-arrow-in-down">Import categories</x-ui.menu-item>
    </x-ui.actions-menu>
    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> New category
    </a>
@endsection

@php
    $sortLink = fn (string $column) => request()->fullUrlWithQuery([
        'sort' => $column,
        'order' => request('sort') === $column && request('order') === 'asc' ? 'desc' : 'asc',
    ]);
    $sortIcon = fn (string $column) => request('sort') === $column
        ? (request('order') === 'asc' ? 'bi-sort-up' : 'bi-sort-down')
        : 'bi-arrow-down-up text-subtle';
    $archived = request('status') === 'archived';
@endphp

@section('content')
    <div class="ui-stack">
        @if ($archived)
            <x-ui.archived-notice />
        @endif

        <div class="ui-stats">
            <x-ui.stat label="Categories" :value="$stats['total_categories']" icon="bi-diagram-3" tone="primary" />
            <x-ui.stat label="Categorized products" :value="$stats['total_products_linked']" icon="bi-box" tone="success" />
            <x-ui.stat label="Most populated" :value="$stats['most_populated']->name ?? '—'" icon="bi-trophy" tone="info"
                :hint="($stats['most_populated']->products_count ?? 0) . ' product(s)'" />
        </div>

        <div>
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#list" type="button"
                        role="tab" aria-controls="list" aria-selected="true">
                        <i class="bi bi-list-ul" aria-hidden="true"></i> List
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="chart-tab" data-bs-toggle="tab" data-bs-target="#chart" type="button"
                        role="tab" aria-controls="chart" aria-selected="false">
                        <i class="bi bi-pie-chart" aria-hidden="true"></i> Distribution
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="list" role="tabpanel" aria-labelledby="list-tab">
                    <x-ui.card flush :pagination="$categories" data-bulk>
                        <x-slot:toolbar>
                            <x-ui.filters :action="route('admin.categories.index')">
                                <x-ui.search placeholder="Category name..." />
                                <x-ui.status-filter />
                            </x-ui.filters>
                        </x-slot:toolbar>

                        <x-ui.bulk-bar export="categories" :archive="route('admin.categories.archive')"
                            :restore="route('admin.categories.restore')" />

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <x-ui.bulk-check-all />
                                        <th>
                                            <a href="{{ $sortLink('name') }}">Name <i class="bi {{ $sortIcon('name') }}" aria-hidden="true"></i></a>
                                        </th>
                                        <th>
                                            <a href="{{ $sortLink('parent_id') }}">Parent <i class="bi {{ $sortIcon('parent_id') }}" aria-hidden="true"></i></a>
                                        </th>
                                        <th class="text-end">Products</th>
                                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($categories as $category)
                                        <tr>
                                            <x-ui.bulk-check :value="$category->id" :label="$category->name" />
                                            <td>
                                                <a href="{{ route('admin.categories.show', $category->id) }}" class="link-plain">{{ $category->name }}</a>
                                            </td>
                                            <td>
                                                @if ($category->parent)
                                                    <span class="ui-tag">{{ $category->parent->name }}</span>
                                                @else
                                                    <span class="text-muted">Root</span>
                                                @endif
                                            </td>
                                            <td class="text-end">{{ $category->products_count }}</td>
                                            <td class="text-end">
                                                <x-ui.actions-menu row>
                                                    <x-ui.menu-item :href="route('admin.categories.show', $category->id)" icon="bi-eye">View</x-ui.menu-item>
                                                    <x-ui.menu-item :href="route('admin.categories.edit', $category->id)" icon="bi-pencil">Edit</x-ui.menu-item>
                                                    @if ($archived)
                                                        <x-ui.menu-item :action="route('admin.categories.restore')" :ids="[$category->id]"
                                                            icon="bi-arrow-counterclockwise">Restore</x-ui.menu-item>
                                                        {{-- Une catégorie qui contient des produits (même archivés) ne peut pas être supprimée --}}
                                                        <li><hr class="dropdown-divider"></li>
                                                        <x-ui.menu-item :action="route('admin.categories.destroy', $category->id)" method="DELETE"
                                                            icon="bi-trash3" tone="danger" :disabled="$category->products_count > 0"
                                                            :title="$category->products_count > 0 ? 'Contains products: it stays archived' : null"
                                                            confirm="Delete this category permanently?">Delete permanently</x-ui.menu-item>
                                                    @else
                                                        <x-ui.menu-item :action="route('admin.categories.archive')" :ids="[$category->id]"
                                                            icon="bi-archive" confirm="Archive this category?"
                                                            confirm-text="It must no longer contain active products or sub-categories.">Archive</x-ui.menu-item>
                                                    @endif
                                                </x-ui.actions-menu>
                                            </td>
                                        </tr>
                                    @empty
                                        <x-ui.empty-row :colspan="5" icon="bi-diagram-3">
                                            {{ $archived ? 'No archived category.' : 'No category found.' }}
                                        </x-ui.empty-row>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </x-ui.card>
                </div>

                <div class="tab-pane fade" id="chart" role="tabpanel" aria-labelledby="chart-tab">
                    <x-ui.card title="Products by parent category" subtitle="Share of the catalog in each top-level category">
                        <div style="height: 360px;">
                            <canvas id="categoryPieChart" aria-label="Products by parent category" role="img"></canvas>
                        </div>
                    </x-ui.card>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        const palette = window.appTheme.chartPalette();
        const data = @json($distribution['data']);

        new Chart(document.getElementById('categoryPieChart'), {
            type: 'doughnut',
            data: {
                labels: @json($distribution['labels']),
                datasets: [{
                    data,
                    backgroundColor: data.map((_, i) => palette[i % palette.length]),
                    borderColor: window.appTheme.token('surface'),
                    borderWidth: 2,
                }],
            },
            options: {
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { position: 'right' },
                },
            },
        });
    </script>
@endpush
