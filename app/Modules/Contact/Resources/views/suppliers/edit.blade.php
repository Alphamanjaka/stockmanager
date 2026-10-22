@extends('layouts.app-back-office')

@section('title', __('Edit supplier'))
@section('subtitle', $supplier->name)

@section('content')
    <form action="{{ route('admin.suppliers.update', $supplier->id) }}" method="POST" class="ui-form-page">
        @csrf
        @method('PUT')
        <x-ui.card :title="__('Supplier details')">
            @include('contact::suppliers._form')

            <x-slot:footer>
                <div class="ui-form-actions">
                    <a href="{{ route('admin.suppliers.show', $supplier->id) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> {{ __('Save changes') }}</button>
                </div>
            </x-slot:footer>
        </x-ui.card>
    </form>
@endsection
