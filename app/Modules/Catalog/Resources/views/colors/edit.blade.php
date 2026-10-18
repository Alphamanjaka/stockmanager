@extends('layouts.app-back-office')

@section('title', 'Edit color')
@section('subtitle', $item->name)

@section('content')
    <form action="{{ route('admin.colors.update', $item->id) }}" method="POST" class="ui-form-page">
        @csrf
        @method('PUT')
        <x-ui.card title="Color details">
            @include('catalog::colors._form')

            <x-slot:footer>
                <div class="ui-form-actions">
                    <a href="{{ route('admin.colors.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> Save changes</button>
                </div>
            </x-slot:footer>
        </x-ui.card>
    </form>
@endsection
