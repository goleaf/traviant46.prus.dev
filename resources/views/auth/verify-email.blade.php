@extends('auth.layout')

@section('title', __('Verify your email'))

@section('subtitle', __('Activate premium features and alerts by confirming your email address.'))

@section('content')
    <div class="legacy-verify">
        <p>
            {{ __('Thanks for signing up! Please verify your email address by clicking the link we just sent you. If you did not receive the email, we can send another one immediately.') }}
        </p>

        @if (session('status') === 'verification-link-sent')
            <div class="legacy-callout legacy-callout--success">
                {{ __('A new verification link has been sent to your email address.') }}
            </div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}" class="legacy-form legacy-form--compact">
            @csrf
            <button type="submit" class="legacy-button">
                {{ __('Resend verification email') }}
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="legacy-form legacy-form--compact">
            @csrf
            <button type="submit" class="legacy-button secondary">
                {{ __('Log out') }}
            </button>
        </form>
    </div>
@endsection

@section('aside')
    <h3>{{ __('Secure your stronghold') }}</h3>
    <p>
        {{ __('Confirming your email lets us alert you about sieges, auctions, and alliance diplomacy without delay.') }}
    </p>
    <ul>
        <li>{{ __('Unlock reports and premium analytics tied to your commander profile.') }}</li>
        <li>{{ __('Ensure sitter invites and alliance notifications reach the right inbox.') }}</li>
        <li>{{ __('Resend or adjust settings at any time from your account console.') }}</li>
    </ul>
@endsection
