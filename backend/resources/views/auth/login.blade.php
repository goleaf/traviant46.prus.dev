@extends('auth.layout')

@section('title', __('Sign in'))

@section('content')
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div>
            <label for="login">{{ __('Username or Email') }}</label>
            <input id="login" type="text" name="login" value="{{ old('login') }}" required autofocus autocomplete="username">
        </div>
        <div style="margin-top: 1.25rem;">
            <label for="password">{{ __('Password') }}</label>
            <input id="password" type="password" name="password" required autocomplete="current-password">
        </div>
        <div style="margin-top: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }} style="width:auto;">
            <label for="remember" style="margin:0; font-weight:500;">{{ __('Remember me') }}</label>
        </div>
        <button type="submit" style="margin-top: 1.5rem;">{{ __('Log in') }}</button>
    </form>
    <a href="{{ route('password.request') }}" class="link">{{ __('Forgot your password?') }}</a>
    <a href="{{ route('register') }}" class="link">{{ __('Create a new account') }}</a>
@endsection
