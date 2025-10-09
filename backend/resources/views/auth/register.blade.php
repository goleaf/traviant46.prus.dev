@extends('auth.layout')

@section('title', __('Create account'))

@section('content')
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div>
            <label for="username">{{ __('Username') }}</label>
            <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username">
        </div>
        <div style="margin-top: 1.25rem;">
            <label for="name">{{ __('Display name') }}</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required>
        </div>
        <div style="margin-top: 1.25rem;">
            <label for="email">{{ __('Email address') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
        </div>
        <div style="margin-top: 1.25rem;">
            <label for="password">{{ __('Password') }}</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">
        </div>
        <div style="margin-top: 1.25rem;">
            <label for="password_confirmation">{{ __('Confirm password') }}</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>
        <button type="submit" style="margin-top: 1.5rem;">{{ __('Register') }}</button>
    </form>
    <a href="{{ route('login') }}" class="link">{{ __('Already have an account? Sign in') }}</a>
@endsection
