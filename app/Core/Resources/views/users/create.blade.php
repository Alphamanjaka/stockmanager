@extends('layouts.app-back-office')

@section('title', 'New user')

@section('content')
    <form action="{{ route('admin.users.store') }}" method="POST" class="ui-form-page">
        @csrf
        <x-ui.card title="User account">
            <div class="ui-form-section">
                <h3 class="ui-form-section-title">Profile</h3>
                <div class="row">
                    <x-ui.field class="col-md-6" name="name" label="Name" required :value="old('name')" />
                    <x-ui.field class="col-md-6" name="email" label="Email address" type="email" required autocomplete="off"
                        :value="old('email')" />
                    <x-ui.field class="col-md-6 mb-0" name="role" label="Role" required
                        hint="Administrators use the back office; sellers use the point of sale.">
                        <select name="role" id="role" @class(['form-select', 'is-invalid' => $errors->has('role')]) required>
                            <option value="front_office" @selected(old('role') === 'front_office')>Seller (Front Office)</option>
                            <option value="back_office" @selected(old('role') === 'back_office')>Administrator (Back Office)</option>
                        </select>
                    </x-ui.field>
                </div>
            </div>

            <div class="ui-form-section">
                <h3 class="ui-form-section-title">Password</h3>
                <div class="row">
                    <x-ui.field class="col-md-6 mb-0" name="password" label="Password" type="password" required autocomplete="new-password" />
                    <x-ui.field class="col-md-6 mb-0" name="password_confirmation" label="Confirm password" type="password" required
                        autocomplete="new-password" />
                </div>
            </div>

            <x-slot:footer>
                <div class="ui-form-actions">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> Create user</button>
                </div>
            </x-slot:footer>
        </x-ui.card>
    </form>
@endsection
