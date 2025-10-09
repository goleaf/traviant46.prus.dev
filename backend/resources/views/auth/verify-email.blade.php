@extends('auth.layout')

@section('title', __('Verify your email'))

@section('content')
    <p>{{ __('Thanks for signing up! Please verify your email address by clicking the link we just emailed to you. If you didn\'t receive the email, we will gladly send you another.') }}</p>
    @if (session('status') === 'verification-link-sent')
        <div class="errors" style="background:#dcfce7; color:#166534;">
            {{ __('A new verification link has been sent to your email address.') }}
        </div>
    @endif
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" style="margin-top: 1.5rem;">{{ __('Resend verification email') }}</button>
    </form>
    <form method="POST" action="{{ route('logout') }}" style="margin-top:1rem;">
        @csrf
        <button type="submit" style="background:#e11d48;">{{ __('Logout') }}</button>
    </form>
@endsection
