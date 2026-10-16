@extends('layouts.app-back-office')

@section('title', 'Edit user')
@section('subtitle', $user->name)

@php $isSelf = auth()->id() === $user->id; @endphp

@section('content')
    <form action="{{ route('admin.users.update', $user) }}" method="POST" class="ui-form-page">
        @csrf
        @method('PUT')
        <x-ui.card title="User account">
            <div class="ui-form-section">
                <h3 class="ui-form-section-title">Profile</h3>
                <div class="row">
                    <x-ui.field class="col-md-6" name="name" label="Name" required :value="old('name', $user->name)" />
                    <x-ui.field class="col-md-6" name="email" label="Email address" type="email" required
                        :value="old('email', $user->email)" />
                    <x-ui.field class="col-md-6 mb-0" name="role" label="Role" :hint="$isSelf ? 'You cannot change your own role.' : null">
                        <select name="role" id="role" @class(['form-select', 'is-invalid' => $errors->has('role')]) @disabled($isSelf)>
                            <option value="front_office" @selected(old('role', $user->role) == 'front_office')>Seller (Front Office)</option>
                            <option value="back_office" @selected(old('role', $user->role) == 'back_office')>Administrator (Back Office)</option>
                        </select>
                    </x-ui.field>
                </div>
            </div>

            <div class="ui-form-section">
                <h3 class="ui-form-section-title">Change password</h3>
                <p class="ui-form-section-hint">Leave empty to keep the current password.</p>
                <div class="row">
                    <x-ui.field class="col-md-6 mb-0" name="password" label="New password" type="password" autocomplete="new-password" />
                    <x-ui.field class="col-md-6 mb-0" name="password_confirmation" label="Confirm new password" type="password"
                        autocomplete="new-password" />
                </div>
            </div>

            <x-slot:footer>
                <div class="ui-form-actions">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> Save changes</button>
                </div>
            </x-slot:footer>
        </x-ui.card>
    </form>
@endsection
