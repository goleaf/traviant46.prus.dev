@extends('auth.layout')

@section('title', __('Reset password'))

@section('content')
    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div>
            <label for="email">{{ __('Email address') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>
        <button type="submit" style="margin-top: 1.5rem;">{{ __('Send password reset link') }}</button>
    </form>
    <a href="{{ route('login') }}" class="link">{{ __('Back to login') }}</a>
@endsection
