@extends('layouts.app-back-office')

@section('title', 'Edit customer')
@section('subtitle', $customer->name)

@section('content')
    <form action="{{ route('admin.customers.update', $customer->id) }}" method="POST" class="ui-form-page">
        @csrf
        @method('PUT')
        <x-ui.card title="Customer details">
            @include('contact::customers._form')

            <x-slot:footer>
                <div class="ui-form-actions">
                    <a href="{{ route('admin.customers.show', $customer->id) }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> Save changes</button>
                </div>
            </x-slot:footer>
        </x-ui.card>
    </form>
@endsection
