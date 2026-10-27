@extends('layouts.app-back-office')

@section('title', __('New user'))

@section('content')
    <form action="{{ route('admin.users.store') }}" method="POST" class="ui-form-page">
        @csrf
        <x-ui.card :title="__('User account')">
            <div class="ui-form-section">
                <h3 class="ui-form-section-title">{{ __('Profile') }}</h3>
                <div class="row">
                    <x-ui.field class="col-md-6" name="name" :label="__('Name')" required :value="old('name')" />
                    <x-ui.field class="col-md-6" name="email" :label="__('Email address')" type="email" required autocomplete="off"
                        :value="old('email')" />
                    <x-ui.field class="col-md-6 mb-0" name="role" :label="__('Role')" required
                        :hint="__('What each role can open is listed below.')">
                        <select name="role" id="role" @class(['form-select', 'is-invalid' => $errors->has('role')]) required>
                            @foreach (\App\Core\Support\Role::ALL as $role)
                                <option value="{{ $role }}" @selected(old('role', \App\Core\Support\Role::SELLER) === $role)>{{ \App\Core\Support\Role::label($role) }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                    {{-- Ce que chaque rôle peut ouvrir (App\Core\Support\Role) --}}
                    <div class="col-12 form-text mt-2">
                        <ul class="mb-0 ps-3">
                            @foreach (\App\Core\Support\Role::ALL as $role)
                                <li><strong>{{ \App\Core\Support\Role::label($role) }}</strong> : {{ __("backoffice.role_hints.{$role}") }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <div class="ui-form-section">
                <h3 class="ui-form-section-title">{{ __('Password') }}</h3>
                <div class="row">
                    <x-ui.field class="col-md-6 mb-0" name="password" :label="__('Password')" type="password" required autocomplete="new-password" />
                    <x-ui.field class="col-md-6 mb-0" name="password_confirmation" :label="__('Confirm password')" type="password" required
                        autocomplete="new-password" />
                </div>
            </div>

            <x-slot:footer>
                <div class="ui-form-actions">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> {{ __('Create user') }}</button>
                </div>
            </x-slot:footer>
        </x-ui.card>
    </form>
@endsection
