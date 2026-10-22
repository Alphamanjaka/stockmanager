@extends('layouts.app-back-office')

@section('title', __('New customer'))

@section('content')
    <form action="{{ route('admin.customers.store') }}" method="POST" class="ui-form-page">
        @csrf
        <x-ui.card :title="__('Customer details')">
            @include('contact::customers._form')

            <x-slot:footer>
                <div class="ui-form-actions">
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> {{ __('Save') }}</button>
                </div>
            </x-slot:footer>
        </x-ui.card>
    </form>
@endsection
