@extends('layouts.app-back-office')

@section('title', __('Edit user'))
@section('subtitle', $user->name)

@php $isSelf = auth()->id() === $user->id; @endphp

@section('content')
    <form action="{{ route('admin.users.update', $user) }}" method="POST" class="ui-form-page">
        @csrf
        @method('PUT')
        <x-ui.card :title="__('User account')">
            <div class="ui-form-section">
                <h3 class="ui-form-section-title">{{ __('Profile') }}</h3>
                <div class="row">
                    <x-ui.field class="col-md-6" name="name" :label="__('Name')" required :value="old('name', $user->name)" />
                    <x-ui.field class="col-md-6" name="email" :label="__('Email address')" type="email" required
                        :value="old('email', $user->email)" />
                    <x-ui.field class="col-md-6 mb-0" name="role" :label="__('Role')" :hint="$isSelf ? __('You cannot change your own role.') : null">
                        <select name="role" id="role" @class(['form-select', 'is-invalid' => $errors->has('role')]) @disabled($isSelf)>
                            <option value="front_office" @selected(old('role', $user->role) == 'front_office')>{{ __('Seller (front office)') }}</option>
                            <option value="back_office" @selected(old('role', $user->role) == 'back_office')>{{ __('Administrator (back office)') }}</option>
                        </select>
                    </x-ui.field>
                </div>
            </div>

            <div class="ui-form-section">
                <h3 class="ui-form-section-title">{{ __('Change password') }}</h3>
                <p class="ui-form-section-hint">{{ __('Leave empty to keep the current password.') }}</p>
                <div class="row">
                    <x-ui.field class="col-md-6 mb-0" name="password" :label="__('New password')" type="password" autocomplete="new-password" />
                    <x-ui.field class="col-md-6 mb-0" name="password_confirmation" :label="__('Confirm new password')" type="password"
                        autocomplete="new-password" />
                </div>
            </div>

            <x-slot:footer>
                <div class="ui-form-actions">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> {{ __('Save changes') }}</button>
                </div>
            </x-slot:footer>
        </x-ui.card>
    </form>
@endsection
