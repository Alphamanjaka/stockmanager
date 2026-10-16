@extends('layouts.app-back-office')

@section('title', 'Edit category')
@section('subtitle', $category->name)

@php
    // Sous-catégories proposées : toutes sauf la catégorie elle-même et ses enfants actuels
    $children = $category->children->pluck('name', 'id')->all();
    $available = collect($categories)->except([$category->id, ...array_keys($children)])->all();
@endphp

@section('content')
    <form id="category-form" action="{{ route('admin.categories.update', $category->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-lg-5">
                <x-ui.card title="Category details" class="h-100">
                    <x-ui.field name="parent_id" label="Parent category" hint="Leave empty for a top-level category.">
                        <select name="parent_id" id="parent_id" @class(['form-select', 'is-invalid' => $errors->has('parent_id')])>
                            <option value="">— None (top level) —</option>
                            @foreach ($categoriesParent as $id => $name)
                                @if ($id !== $category->id)
                                    <option value="{{ $id }}" @selected(old('parent_id', $category->parent_id) == $id)>{{ $name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </x-ui.field>
                    <x-ui.field name="name" label="Category name" required maxlength="255" :value="old('name', $category->name)" />
                    <x-ui.field class="mb-0" name="description" label="Description" type="textarea" rows="2"
                        :value="old('description', $category->description)" />
                </x-ui.card>
            </div>

            <div class="col-lg-7">
                <x-ui.card title="Sub-categories" subtitle="Categories placed under this one" class="h-100">
                    @include('catalog::categories._children-picker', ['available' => $available, 'selected' => $children])
                </x-ui.card>
            </div>

            <div class="col-12">
                @include('catalog::categories._option-template', ['template' => $category->option_template])
            </div>
        </div>

        <div class="ui-form-bar ui-form-actions">
            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> Save changes</button>
        </div>
    </form>
@endsection
