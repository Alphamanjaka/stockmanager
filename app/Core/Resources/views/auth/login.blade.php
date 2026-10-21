@extends('layouts.app-auth')

@section('title', 'Sign in')

@section('content')
    <h1 class="auth-title">Sign in</h1>
    <p class="auth-subtitle">Enter your credentials.</p>

    @if ($errors->any())
        <x-ui.alert tone="danger">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif

    <form method="POST" action="{{ route('auth.login') }}">
        @csrf

        <x-ui.field name="email" label="Email address" type="email" :value="old('email')" required autofocus
            autocomplete="username" placeholder="name@company.com" class="mb-3" />

        <div class="ui-field">
            <label for="password" class="form-label required">Password</label>
            <div class="input-group">
                <input type="password" id="password" name="password" required autocomplete="current-password"
                    @class(['form-control', 'is-invalid' => $errors->has('password')])>
                <button type="button" class="btn btn-outline-secondary" id="toggle-password" aria-label="Show password"
                    title="Show password">
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
            </div>
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
            <label class="form-check-label" for="remember">Remember me</label>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100">
            <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Sign in
        </button>
    </form>
@endsection

@push('scripts')
    <script>
        document.getElementById('toggle-password').addEventListener('click', function () {
            const input = document.getElementById('password');
            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            this.querySelector('.bi').className = visible ? 'bi bi-eye' : 'bi bi-eye-slash';
        });
    </script>
@endpush
