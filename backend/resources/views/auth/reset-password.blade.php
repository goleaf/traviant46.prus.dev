@extends('auth.layout')

@section('title', __('Choose a new password'))

@section('content')
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div>
            <label for="email">{{ __('Email address') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus>
        </div>
        <div style="margin-top: 1.25rem;">
            <label for="password">{{ __('Password') }}</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">
        </div>
        <div style="margin-top: 1.25rem;">
            <label for="password_confirmation">{{ __('Confirm password') }}</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>
        <button type="submit" style="margin-top: 1.5rem;">{{ __('Reset password') }}</button>
    </form>
@endsection
