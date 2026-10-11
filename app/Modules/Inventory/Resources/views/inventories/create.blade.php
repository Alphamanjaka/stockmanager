@extends('layouts.app-back-office')

@section('title', 'New Stock Count')

@section('content')
    <div class="row">
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('admin.inventories.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Scope</label>
                            <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror">
                                <option value="">All products</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea name="notes" id="notes" rows="3" class="form-control"
                                placeholder="Ex: annual count, warehouse A...">{{ old('notes') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Open the stock count</button>
                        <a href="{{ route('admin.inventories.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="alert alert-info">
                <h6 class="fw-bold">How it works</h6>
                <ol class="mb-0 small ps-3">
                    <li>Opening the count records the current system stock of every variant in the scope.</li>
                    <li>Enter the quantities you physically count; you can save and continue later.</li>
                    <li>Validating sets the stock of each counted line to the counted quantity and records a
                        stock movement for every gap. Lines left empty are not changed.</li>
                </ol>
            </div>
        </div>
    </div>
@endsection
