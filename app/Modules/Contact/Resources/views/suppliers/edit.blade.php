@extends('layouts.app-back-office')

@section('title', 'Edit supplier')
@section('subtitle', $supplier->name)

@section('content')
    <form action="{{ route('admin.suppliers.update', $supplier->id) }}" method="POST" class="ui-form-page">
        @csrf
        @method('PUT')
        <x-ui.card title="Supplier details">
            @include('contact::suppliers._form')

            <x-slot:footer>
                <div class="ui-form-actions">
                    <a href="{{ route('admin.suppliers.show', $supplier->id) }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> Save changes</button>
                </div>
            </x-slot:footer>
        </x-ui.card>
    </form>
@endsection
