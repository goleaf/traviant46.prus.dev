@extends('auth.layout')

@section('title', __('Reset password'))

@section('subtitle', __('We will email you a secure link to reset your password.'))

@section('content')
    <form method="POST" action="{{ route('password.email') }}" class="legacy-form" autocomplete="on">
        @csrf

        <table class="transparent loginTable">
            <tbody>
                <tr class="email">
                    <th>{{ __('Registered email') }}</th>
                    <td>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
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
                <tr class="submit">
                    <th></th>
                    <td>
                        <button type="submit" class="legacy-button">
                            {{ __('Send password reset link') }}
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
@endsection

@section('footer')
    <div class="legacy-links">
        <a href="{{ route('login') }}">{{ __('Back to login') }}</a>
        <a href="{{ route('register') }}">{{ __('Create a new account') }}</a>
    </div>
@endsection

@section('aside')
    <h3>{{ __('Recover access swiftly') }}</h3>
    <p>
        {{ __('Use your email to generate a secure recovery link. Laravel Fortify handles the reset so you can return to strategizing without delay.') }}
    </p>
    <ul>
        <li>{{ __('Messages land in your inbox moments after the request is confirmed.') }}</li>
        <li>{{ __('Links expire quickly to protect your commanders and resources.') }}</li>
        <li>{{ __('Need a fresh start? Register a new account and begin a new campaign.') }}</li>
    </ul>
@endsection
