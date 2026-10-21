@extends('layouts.app-back-office')

@section('title', __('Help & support'))

@section('content')
    <div class="ui-form-page">
        <x-ui.card :title="__('Welcome to StockMaster help')">
            <p class="mb-0">
                {{ __('Here you will find resources and guides to use the application effectively.') }}
                {!! __('Keyboard shortcut: :keys opens the module launcher from any page.', ['keys' => '<kbd>Alt</kbd> + <kbd>L</kbd>']) !!}
            </p>
        </x-ui.card>
    </div>
@endsection
