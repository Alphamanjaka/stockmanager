@extends('layouts.app-back-office')

@section('title', 'New customer')

@section('content')
    <form action="{{ route('admin.customers.store') }}" method="POST" class="ui-form-page">
        @csrf
        <x-ui.card title="Customer details">
            @include('contact::customers._form')

            <x-slot:footer>
                <div class="ui-form-actions">
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> Save</button>
                </div>
            </x-slot:footer>
        </x-ui.card>
    </form>
@endsection
