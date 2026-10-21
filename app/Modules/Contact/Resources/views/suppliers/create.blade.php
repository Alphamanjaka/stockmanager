@extends('layouts.app-back-office')

@section('title', __('New supplier'))

@section('content')
    <form action="{{ route('admin.suppliers.store') }}" method="POST" class="ui-form-page">
        @csrf
        <x-ui.card :title="__('Supplier details')">
            @include('contact::suppliers._form', ['supplier' => null])

            <x-slot:footer>
                <div class="ui-form-actions">
                    <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> {{ __('Save') }}</button>
                </div>
            </x-slot:footer>
        </x-ui.card>
    </form>
@endsection
