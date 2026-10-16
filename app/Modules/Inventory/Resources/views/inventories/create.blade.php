@extends('layouts.app-back-office')

@section('title', 'New stock count')

@section('content')
    <div class="row g-3">
        <div class="col-lg-7">
            <form action="{{ route('admin.inventories.store') }}" method="POST">
                @csrf
                <x-ui.card title="Stock count">
                    <x-ui.field name="category_id" label="Scope" hint="Only the variants of this category will be counted.">
                        <select name="category_id" id="category_id" @class(['form-select', 'is-invalid' => $errors->has('category_id')])>
                            <option value="">All products</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                    <x-ui.field class="mb-0" name="notes" label="Notes" type="textarea" rows="3"
                        placeholder="e.g. annual count, warehouse A..." :value="old('notes')" />

                    <x-slot:footer>
                        <div class="ui-form-actions">
                            <a href="{{ route('admin.inventories.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-clipboard-check" aria-hidden="true"></i> Open the stock count
                            </button>
                        </div>
                    </x-slot:footer>
                </x-ui.card>
            </form>
        </div>

        <div class="col-lg-5">
            <x-ui.alert tone="info" title="How it works">
                <ol class="mb-0 ps-3">
                    <li>Opening the count records the current system stock of every variant in the scope.</li>
                    <li>Enter the quantities you physically count; you can save and continue later.</li>
                    <li>Validating sets the stock of each counted line to the counted quantity and records a
                        stock movement for every gap. Lines left empty are not changed.</li>
                </ol>
            </x-ui.alert>
        </div>
    </div>
@endsection
