@extends('auth.layout')

@section('title', __('Choose a new password'))

@section('subtitle', __('Securely set a new password to regain access.'))

@section('content')
    <form method="POST" action="{{ route('password.update') }}" class="legacy-form" autocomplete="on">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <table class="transparent loginTable">
            <tbody>
                <tr class="email">
                    <th>{{ __('Email address') }}</th>
                    <td>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email', $request->email) }}"
                            class="legacy-input"
                            required
                            autofocus
                            autocomplete="email"
                        >
                        @error('email')
                            <div class="legacy-error">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
                <tr class="password">
                    <th>{{ __('New password') }}</th>
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
                            {{ __('Reset password') }}
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
@endsection

@section('footer')
    <div class="legacy-links">
        <a href="{{ route('login') }}">{{ __('Return to login') }}</a>
    </div>
@endsection

@section('aside')
    <h3>{{ __('Reclaim your empire') }}</h3>
    <p>
        {{ __('Set a strong password to regain control over your Travian realm. The Laravel Fortify reset flow keeps intruders at bay while restoring your command.') }}
    </p>
    <ul>
        <li>{{ __('Use a mix of letters, numbers, and symbols for the toughest defenses.') }}</li>
        <li>{{ __('Passwords update instantly across sitter sessions and alliance tools.') }}</li>
        <li>{{ __('Log back in to resume growth, diplomacy, and conquest.') }}</li>
    </ul>
@endsection
