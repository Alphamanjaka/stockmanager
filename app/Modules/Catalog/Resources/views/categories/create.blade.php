@extends('layouts.app-back-office')

@section('title', 'New category')

@section('content')
    <form action="{{ route('admin.categories.store') }}" method="POST" id="category-form">
        @csrf
        <div class="row g-3">
            <div class="col-lg-5">
                <x-ui.card title="Category details" class="h-100">
                    <x-ui.field name="parent_id" label="Parent category" hint="Leave empty for a top-level category.">
                        <select name="parent_id" id="parent_id" @class(['form-select', 'is-invalid' => $errors->has('parent_id')])>
                            <option value="">— None (top level) —</option>
                            @foreach ($categories as $id => $name)
                                <option value="{{ $id }}" @selected(old('parent_id') == $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                    <x-ui.field name="name" label="Category name" required maxlength="255" :value="old('name')" />
                    <x-ui.field class="mb-0" name="description" label="Description" type="textarea" rows="2"
                        :value="old('description')" />
                </x-ui.card>
            </div>

            <div class="col-lg-7">
                <x-ui.card title="Sub-categories" subtitle="Existing categories placed under this one" class="h-100">
                    @include('catalog::categories._children-picker', ['available' => $categoriesChildrenAvalaibles, 'selected' => []])
                </x-ui.card>
            </div>

            <div class="col-12">
                @include('catalog::categories._option-template', ['template' => null])
            </div>
        </div>

        <div class="ui-form-bar ui-form-actions">
            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> Save category</button>
        </div>
    </form>
@endsection
