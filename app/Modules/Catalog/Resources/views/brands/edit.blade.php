@extends('layouts.app-back-office')

@section('title', __('Edit brand'))
@section('subtitle', $brand->name)

@section('content')
    <form action="{{ route('admin.brands.update', $brand->id) }}" method="POST" class="ui-form-page">
        @csrf
        @method('PUT')
        <x-ui.card :title="__('Brand details')">
            @include('catalog::brands._form')

            <x-slot:footer>
                <div class="ui-form-actions">
                    <a href="{{ route('admin.brands.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> {{ __('Save changes') }}</button>
                </div>
            </x-slot:footer>
        </x-ui.card>
    </form>
@endsection
