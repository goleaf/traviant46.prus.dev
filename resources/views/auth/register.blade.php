@extends('auth.layout')

@section('title', __('Create account'))

@section('subtitle', __('Launch a new Travian account with secure authentication defaults.'))

@section('content')
    <form method="POST" action="{{ route('register') }}" class="legacy-form" autocomplete="on">
        @csrf

        <table class="transparent loginTable">
            <tbody>
                <tr class="account-name">
                    <th>{{ __('Account name') }}</th>
                    <td>
                        <input
                            id="username"
                            type="text"
                            name="username"
                            value="{{ old('username') }}"
                            class="legacy-input"
                            required
                            autofocus
                            autocomplete="username"
                        >
                        @error('username')
                            <div class="legacy-error">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
                <tr class="display-name">
                    <th>{{ __('Display name') }}</th>
                    <td>
                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            class="legacy-input"
                            required
                        >
                        @error('name')
                            <div class="legacy-error">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
                <tr class="email">
                    <th>{{ __('Email address') }}</th>
                    <td>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="legacy-input"
                            required
                            autocomplete="email"
                        >
                        @error('email')
                            <div class="legacy-error">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
                <tr class="password">
                    <th>{{ __('Password') }}</th>
                    <td>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            class="legacy-input"
                            required
                            autocomplete="new-password"
                        >
                        @error('password')
                            <div class="legacy-error">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
                <tr class="password-confirmation">
                    <th>{{ __('Confirm password') }}</th>
                    <td>
                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            class="legacy-input"
                            required
                            autocomplete="new-password"
                        >
                    </td>
                </tr>
                <tr class="submit">
                    <th></th>
                    <td>
                        <button type="submit" class="legacy-button">
                            {{ __('Create my Travian account') }}
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
@endsection

@section('footer')
    <div class="legacy-links">
        <a href="{{ route('login') }}">{{ __('Already have an account? Sign in') }}</a>
    </div>
@endsection

@section('aside')
    <h3>{{ __('Forge your legacy') }}</h3>
    <p>
        {{ __('Register to begin expanding villages, forging alliances, and mastering economy cycles in this faithful Travian remake.') }}
    </p>
    <ul>
        <li>{{ __('Choose a unique commander name to represent your tribe.') }}</li>
        <li>{{ __('Securely store contact details for sitter and recovery workflows.') }}</li>
        <li>{{ __('Start with Laravel-backed protection against imposters and bots.') }}</li>
    </ul>
@endsection
