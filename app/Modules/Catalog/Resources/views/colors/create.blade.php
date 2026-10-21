@extends('layouts.app-back-office')

@section('title', __('New color'))

@section('content')
    <form action="{{ route('admin.colors.store') }}" method="POST" class="ui-form-page">
        @csrf
        <x-ui.card :title="__('Color details')">
            @include('catalog::colors._form', ['item' => null])

            <x-slot:footer>
                <div class="ui-form-actions">
                    <a href="{{ route('admin.colors.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> {{ __('Save') }}</button>
                </div>
            </x-slot:footer>
        </x-ui.card>
    </form>
@endsection
