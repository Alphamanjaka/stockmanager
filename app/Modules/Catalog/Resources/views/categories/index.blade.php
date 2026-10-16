@extends('layouts.app-back-office')

@section('title', 'Categories')

@section('actions')
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
@endphp

@section('content')
    <div class="ui-stack">
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
                    <x-ui.card flush :pagination="$categories">
                        <x-slot:toolbar>
                            <x-ui.filters :action="route('admin.categories.index')">
                                <x-ui.search placeholder="Category name..." />
                                <select name="category" class="form-select ui-filter" aria-label="Category">
                                    <option value="">All categories</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}" @selected(request('category') == $cat->id)>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </x-ui.filters>
                        </x-slot:toolbar>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
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
                                                <div class="ui-row-actions">
                                                    <a href="{{ route('admin.categories.edit', $category->id) }}" class="btn btn-icon" title="Edit" aria-label="Edit">
                                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                                    </a>
                                                    <x-ui.delete-button :action="route('admin.categories.destroy', $category->id)"
                                                        confirm="Delete this category?" />
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <x-ui.empty-row :colspan="4" icon="bi-diagram-3">No category found.</x-ui.empty-row>
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
