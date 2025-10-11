@extends('auth.layout')

@section('title', __('Verify your email'))

@section('subtitle', __('Activate premium features and alerts by confirming your email address.'))

@section('content')
    <div class="space-y-6 text-sm text-slate-200">
        <p>{{ __('Thanks for signing up! Please verify your email address by clicking the link we just sent you. If you didn\'t receive the email, we can send another one immediately.') }}</p>

        @if (session('status') === 'verification-link-sent')
            <flux:callout variant="filled" icon="check-circle" class="text-left">
                {{ __('A new verification link has been sent to your email address.') }}
            </flux:callout>
        @endif

        <form method="POST" action="{{ route('verification.send') }}" class="space-y-3">
            @csrf
            <flux:button type="submit" variant="primary" color="sky" class="w-full">
                {{ __('Resend verification email') }}
            </flux:button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="space-y-3">
            @csrf
            <flux:button type="submit" variant="danger" class="w-full">
                {{ __('Log out') }}
            </flux:button>
        </form>
    </div>
@endsection
